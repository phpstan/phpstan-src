<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;
use PHPStan\File\FileFinder;
use PHPStan\File\FileStatSignatures;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_keys;
use function fclose;
use function flock;
use function fopen;
use function hrtime;
use function sha1;
use function sprintf;
use function usleep;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;

/**
 * Builds the symbol maps of the optimized directory source locators: which file declares which
 * class, function and constant.
 *
 * Finding the symbols means reading every file, so what was found is cached per directory, and a
 * file is only scanned again once it changed. A file whose stat signature is what it was when it was
 * scanned has not changed (see FileStatSignatures) - checking that costs a fraction of reading the
 * file. When the signature cannot vouch for the file, its content hash decides.
 */
#[AutowiredService]
final class OptimizedDirectorySourceLocatorFactory
{

	/**
	 * Give up waiting for the scan lock after this long and scan the directory anyway, so a wedged
	 * winner or a foreign phpstan process holding the shared lock cannot block a worker until
	 * parallel.processTimeout. A directory scan finishes in well under a second, so this is far above
	 * any legitimate wait.
	 */
	private const SCAN_LOCK_WAIT_SECONDS_LIMIT = 10.0;

	private const SCAN_LOCK_POLL_INTERVAL_MICROSECONDS = 50_000;

	/**
	 * The files this process already checked or scanned, as the caches keep them: [content hash,
	 * stat signature, classes, functions, constants]. The directories a process builds locators for
	 * can overlap, and a file reachable from two of them is then looked at once.
	 *
	 * @var array<string, array{string, string|null, string[], string[], string[]}>
	 */
	private array $checkedFiles = [];

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $fileFinder,
		private PhpVersion $phpVersion,
		private SymbolFinderInFiles $symbolFinderInFiles,
		private Cache $cache,
		private FileContentHasher $fileContentHasher,
		private FileStatSignatures $fileStatSignatures,
		#[AutowiredParameter]
		private string $tmpDir,
	)
	{
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		$batch = $this->createBatch();
		$locator = $batch->createByDirectory($directory);
		$batch->scan();

		return $locator;
	}

	/**
	 * @param string[] $files
	 * @param non-empty-string&literal-string $uniqueCacheIdentifier
	 */
	public function createByFiles(array $files, string $uniqueCacheIdentifier): OptimizedDirectorySourceLocator
	{
		$batch = $this->createBatch();
		$locator = $batch->createByFiles($files, $uniqueCacheIdentifier);
		$batch->scan();

		return $locator;
	}

	/**
	 * For creating several locators and scanning them in one go.
	 */
	public function createBatch(): OptimizedDirectorySourceLocatorBatch
	{
		return new OptimizedDirectorySourceLocatorBatch(
			fn (string $directory): array => $this->fileFinder->findFiles([$directory])->getFiles(),
			fn (): OptimizedDirectorySourceLocator => new OptimizedDirectorySourceLocator(
				$this->fileNodesFetcher,
				$this->cache,
				$this->phpVersion,
				$this->fileContentHasher,
				[],
				[],
				[],
				awaitingScan: true,
			),
			function (array $requests): void {
				$this->scan($requests);
			},
		);
	}

	/**
	 * Fills in the symbol maps of the locators, each for its files, from their caches and by
	 * scanning the files that changed since.
	 *
	 * @param list<array{non-empty-string, string[], OptimizedDirectorySourceLocator}> $requests
	 */
	private function scan(array $requests): void
	{
		$variableCacheKey = sprintf('v2-%s', $this->phpVersion->supportsEnums() ? 'enums' : 'no-enums');
		$signatures = $this->fileStatSignatures->begin();
		$scanLocks = [];

		try {
			$cachedEntries = [];
			foreach ($requests as $i => [$cacheKey]) {
				$cached = $this->loadCachedSymbols($cacheKey, $variableCacheKey);
				// On a cold cache every parallel worker that is not forked from a process that did
				// the scan already builds the same locators at once, and would scan the same files.
				// The first worker to take the lock scans and saves; the rest block until it
				// releases, then read the cache it wrote. The locators of a batch can share a cache
				// key (odsl-installed-files of two Composer projects), and a second lock of the same
				// file would wait for this very process.
				if ($cached === null && !array_key_exists($cacheKey, $scanLocks)) {
					$scanLock = $this->acquireDirectoryScanLock($cacheKey . $variableCacheKey);
					if ($scanLock !== null) {
						$cached = $this->loadCachedSymbols($cacheKey, $variableCacheKey);
						if ($cached !== null) {
							$this->releaseDirectoryScanLock($scanLock);
						} else {
							$scanLocks[$cacheKey] = $scanLock;
						}
					}
				}

				$cachedEntries[$i] = $cached;
			}

			$filesToScan = [];
			foreach ($requests as $i => [, $files]) {
				$cached = $cachedEntries[$i] ?? [];
				foreach ($files as $file) {
					if (array_key_exists($file, $this->checkedFiles) || array_key_exists($file, $filesToScan)) {
						continue;
					}

					$signature = $signatures->get($file);
					$cachedFile = $cached[$file] ?? null;
					if ($cachedFile !== null && $signature !== null && $cachedFile[1] === $signature) {
						$this->checkedFiles[$file] = $cachedFile;
						continue;
					}

					$hash = $this->fileContentHasher->hash($file);
					if ($hash === false) {
						continue;
					}

					if ($cachedFile !== null && $cachedFile[0] === $hash) {
						$this->checkedFiles[$file] = [$hash, $signature, $cachedFile[2], $cachedFile[3], $cachedFile[4]];
						continue;
					}

					$filesToScan[$file] = [$hash, $signature];
				}
			}

			if ($filesToScan !== []) {
				$foundSymbols = $this->symbolFinderInFiles->findSymbols(array_keys($filesToScan), $this->phpVersion->supportsEnums());
				foreach ($filesToScan as $file => [$hash, $signature]) {
					[$classes, $functions, $constants] = $foundSymbols[$file] ?? [[], [], []];
					$this->checkedFiles[$file] = [$hash, $signature, $classes, $functions, $constants];
				}
			}

			foreach ($requests as $i => [$cacheKey, $files, $locator]) {
				$entry = [];
				foreach ($files as $file) {
					if (!array_key_exists($file, $this->checkedFiles)) {
						continue;
					}

					$entry[$file] = $this->checkedFiles[$file];
				}

				// A warm run finds every file unchanged and does not write anything. A cold miss is
				// written even when empty, so that the workers waiting for the lock read it back.
				if ($entry !== $cachedEntries[$i]) {
					$this->cache->save($cacheKey, $variableCacheKey, $entry);
				}

				[$classToFile, $functionToFiles, $constantToFile] = $this->changeStructure($entry);
				$locator->fillScanned($classToFile, $functionToFiles, $constantToFile);
			}
		} finally {
			// Release even if scanning or saving throws, so a failing worker cannot leave other
			// workers blocked on the lock until it exits.
			foreach ($scanLocks as $scanLock) {
				$this->releaseDirectoryScanLock($scanLock);
			}
		}
	}

	/**
	 * Take an exclusive cross-process lock for a directory's symbol scan so that on a cold cache only
	 * the first worker scans it. Best effort: a null return means locking is unavailable or the wait
	 * timed out, and the caller scans as before. The returned handle is held until
	 * {@see releaseDirectoryScanLock()}; the OS releases the lock if the process dies while holding it.
	 *
	 * @return resource|null
	 */
	private function acquireDirectoryScanLock(string $lockKey)
	{
		$lockDirectory = sprintf('%s/cache/locks', $this->tmpDir);
		try {
			DirectoryCreator::ensureDirectoryExists($lockDirectory, 0777);
		} catch (DirectoryCreatorException) {
			return null;
		}

		// The lock files are zero-byte markers, never written to and never removed - they are reused
		// across runs. A tmp reaper (e.g. systemd-tmpfiles) unlinking one while it is held is harmless:
		// the next fopen('c') creates a fresh inode and two workers may scan the same directory at once,
		// which is exactly the pre-lock race and stays correct because the cache save is atomic.
		$lockHandle = @fopen(sprintf('%s/odsl-%s.lock', $lockDirectory, sha1($lockKey)), 'c');
		if ($lockHandle === false) {
			return null;
		}

		// Poll for the lock with a deadline instead of blocking forever: a live-but-wedged winner, or a
		// foreign phpstan process holding the shared odsl-installed-files lock, would otherwise block
		// this worker until parallel.processTimeout. On timeout we scan the directory ourselves, capping
		// the damage at the pre-lock behaviour for that directory.
		$deadline = hrtime(true) + (int) (self::SCAN_LOCK_WAIT_SECONDS_LIMIT * 1_000_000_000);
		while (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
			if (hrtime(true) >= $deadline) {
				@fclose($lockHandle);
				return null;
			}

			usleep(self::SCAN_LOCK_POLL_INTERVAL_MICROSECONDS);
		}

		return $lockHandle;
	}

	/**
	 * @param non-empty-string $cacheKey
	 * @return array<string, array{string, string|null, string[], string[], string[]}>|null
	 */
	private function loadCachedSymbols(string $cacheKey, string $variableCacheKey): ?array
	{
		/** @var array<string, array{string, string|null, string[], string[], string[]}>|null $cached */
		$cached = $this->cache->load($cacheKey, $variableCacheKey);

		return $cached;
	}

	/**
	 * @param resource $lockHandle
	 */
	private function releaseDirectoryScanLock($lockHandle): void
	{
		@flock($lockHandle, LOCK_UN);
		@fclose($lockHandle);
	}

	/**
	 * @param array<string, array{string, string|null, string[], string[], string[]}> $entry
	 * @return array{array<string, string>, array<string, array<int, string>>, array<string, string>}
	 */
	private function changeStructure(array $entry): array
	{
		$classToFile = [];
		$constantToFile = [];
		$functionToFiles = [];
		foreach ($entry as $file => [, , $classes, $functions, $constants]) {
			foreach ($classes as $classInFile) {
				$classToFile[$classInFile] = $file;
			}
			foreach ($functions as $functionInFile) {
				if (!array_key_exists($functionInFile, $functionToFiles)) {
					$functionToFiles[$functionInFile] = [];
				}
				$functionToFiles[$functionInFile][] = $file;
			}
			foreach ($constants as $constantInFile) {
				$constantToFile[$constantInFile] = $file;
			}
		}

		return [
			$classToFile,
			$functionToFiles,
			$constantToFile,
		];
	}

}
