<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\ArenaCache;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;
use PHPStan\File\FileFinder;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_keys;
use function fclose;
use function flock;
use function fopen;
use function hrtime;
use function is_array;
use function ksort;
use function serialize;
use function sha1;
use function sprintf;
use function usleep;
use const LOCK_EX;
use const LOCK_NB;
use const LOCK_UN;
use const SORT_STRING;

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
	 * The hash lock is polled much finer than the scan lock: every worker
	 * reaches the same directories in near lockstep at startup, and most
	 * directories hash in well under the scan lock's 50ms tick, so a coarse
	 * poll would make lock losers sleep longer than the work they skip.
	 */
	private const HASH_LOCK_POLL_INTERVAL_MICROSECONDS = 5_000;

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $fileFinder,
		private PhpVersion $phpVersion,
		private SymbolFinderInFiles $symbolFinderInFiles,
		private Cache $cache,
		private FileContentHasher $fileContentHasher,
		#[AutowiredParameter]
		private string $tmpDir,
	)
	{
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		$cacheKey = sprintf('odsl-%s', $directory);
		$hashesRecordKey = 'odsl-filehashes-' . $directory;

		// The walk + hash of a directory is identical in every process of a
		// run, and running it once per worker in parallel multiplies both the
		// CPU and — on hosts where concurrent open() is expensive — the wall
		// cost of the analysis startup. When the run has a shared arena, the
		// first process publishes the file-hash map and everyone else reuses
		// it. hasRecord() on the analysed-files record (published by the
		// master before workers spawn) doubles as the "is an arena active?"
		// probe — the seam has no explicit method for that and only grows one
		// together with the extension.
		$arenaActive = ArenaCache::hasRecord('analysed-files');
		$hashesLock = null;
		if ($arenaActive) {
			$shared = ArenaCache::lookup($hashesRecordKey);
			if (is_array($shared)) {
				/** @var array<string, string> $shared */
				return $this->createCachedDirectorySourceLocator($shared, $cacheKey);
			}

			// Single-flight the walk + hash, same pattern as the cold-cache
			// scan below: the winner computes and publishes, losers wait and
			// re-read the record. A lost lock (timeout, unwritable tmp) just
			// means this worker hashes the directory itself.
			$hashesLock = $this->acquireDirectoryScanLock('hashes-' . $directory, self::HASH_LOCK_POLL_INTERVAL_MICROSECONDS);
			if ($hashesLock !== null) {
				$shared = ArenaCache::lookup($hashesRecordKey);
				if (is_array($shared)) {
					$this->releaseDirectoryScanLock($hashesLock);

					/** @var array<string, string> $shared */
					return $this->createCachedDirectorySourceLocator($shared, $cacheKey);
				}
			}
		}

		try {
			$files = $this->fileFinder->findFiles([$directory])->getFiles();
			$fileHashes = [];
			foreach ($files as $file) {
				$hash = $this->fileContentHasher->hash($file);
				if ($hash === false) {
					continue;
				}
				$fileHashes[$file] = $hash;
			}

			if ($arenaActive) {
				ArenaCache::publish($hashesRecordKey, $fileHashes);
			}
		} finally {
			if ($hashesLock !== null) {
				$this->releaseDirectoryScanLock($hashesLock);
			}
		}

		return $this->createCachedDirectorySourceLocator($fileHashes, $cacheKey);
	}

	/**
	 * @param array<string, string> $fileHashes
	 * @param non-empty-string $cacheKey
	 */
	private function createCachedDirectorySourceLocator(array $fileHashes, string $cacheKey): OptimizedDirectorySourceLocator
	{
		$variableCacheKey = sprintf('v1-%s', $this->phpVersion->supportsEnums() ? 'enums' : 'no-enums');

		// The run's shared arena binds the symbol index to the exact content
		// fingerprint of the directory: a worker seeing the same file hashes
		// reuses the index another process already validated and published —
		// no include() of the cache blob, no validation pass, and names are
		// materialized lazily one by one. A worker whose view differs (a file
		// changed mid-run) misses the fingerprint and builds locally.
		$sortedFileHashes = $fileHashes;
		ksort($sortedFileHashes, SORT_STRING);
		$arenaKeyPrefix = sprintf('odsl-arena-%s', sha1($cacheKey . "\0" . $variableCacheKey . "\0" . serialize($sortedFileHashes)));
		if (
			ArenaCache::hasRecord($arenaKeyPrefix . '-classes')
			&& ArenaCache::hasRecord($arenaKeyPrefix . '-functions')
			&& ArenaCache::hasRecord($arenaKeyPrefix . '-constants')
		) {
			return new OptimizedDirectorySourceLocator(
				$this->fileNodesFetcher,
				$this->cache,
				$this->phpVersion,
				$this->fileContentHasher,
				[],
				[],
				[],
				$arenaKeyPrefix,
			);
		}

		$originalFileHashes = $fileHashes;

		$cached = $this->loadCachedSymbols($cacheKey, $variableCacheKey);

		$scanLock = null;
		if ($cached === null) {
			// On a cold cache every parallel worker builds the same directory locator at once and would
			// scan the same directory redundantly. A scan is not published until it finishes and the save
			// is atomic, so these races are wasteful rather than unsafe. The first worker to take the lock
			// scans and saves; the rest block until it releases, then re-read the cache it wrote. When the
			// re-read hits, the lock has done its job, so release it right away and continue lock-free -
			// the validation and any (re)scan below then run exactly as they did before this change.
			$scanLock = $this->acquireDirectoryScanLock($cacheKey . $variableCacheKey);
			if ($scanLock !== null) {
				$cached = $this->loadCachedSymbols($cacheKey, $variableCacheKey);
				if ($cached !== null) {
					$this->releaseDirectoryScanLock($scanLock);
					$scanLock = null;
				}
			}
		}

		try {
			$cacheModified = false;
			$findInFiles = [];
			if ($cached !== null) {
				foreach ($cached as $file => [$hash, $classes, $functions, $constants]) {
					if (!array_key_exists($file, $fileHashes)) {
						unset($cached[$file]);
						$cacheModified = true;
						continue;
					}
					$newHash = $fileHashes[$file];
					unset($fileHashes[$file]);
					if ($hash === $newHash) {
						continue;
					}

					$findInFiles[] = $file;
				}
			} else {
				// Cold miss: publish the result (even an empty one) so lock losers read it back instead
				// of finding the cache still cold and re-scanning the directory themselves.
				$cached = [];
				$cacheModified = true;
			}

			foreach (array_keys($fileHashes) as $file) {
				$findInFiles[] = $file;
			}

			if ($findInFiles !== []) {
				$cacheModified = true;
				foreach ($this->symbolFinderInFiles->findSymbols($findInFiles, $this->phpVersion->supportsEnums()) as $scannedFile => [$newClasses, $newFunctions, $newConstants]) {
					$newHash = $originalFileHashes[$scannedFile];
					$cached[$scannedFile] = [$newHash, $newClasses, $newFunctions, $newConstants];
				}
			}

			// Only write when the cache actually changed. A lock loser re-reads exactly what the winner
			// wrote, and a warm run finds every hash unchanged, so both would otherwise re-serialize and
			// re-write the identical symbol table - the loser while still holding the lock.
			if ($cacheModified) {
				$this->cache->save($cacheKey, $variableCacheKey, $cached);
			}
		} finally {
			// Release even if scanning or saving throws, so a failing worker cannot leave other
			// workers blocked on the lock until it exits.
			if ($scanLock !== null) {
				$this->releaseDirectoryScanLock($scanLock);
			}
		}

		[$classToFile, $functionToFiles, $constantToFile] = $this->changeStructure($cached);

		// Publication order matters: the reader above requires all three
		// records, so a partially-published index is never consumed.
		ArenaCache::publishHash($arenaKeyPrefix . '-classes', $classToFile);
		ArenaCache::publishHash($arenaKeyPrefix . '-functions', $functionToFiles);
		ArenaCache::publishHash($arenaKeyPrefix . '-constants', $constantToFile);

		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->cache,
			$this->phpVersion,
			$this->fileContentHasher,
			$classToFile,
			$functionToFiles,
			$constantToFile,
		);
	}

	/**
	 * Take an exclusive cross-process lock for a directory's symbol scan so that on a cold cache only
	 * the first worker scans it. Best effort: a null return means locking is unavailable or the wait
	 * timed out, and the caller scans as before. The returned handle is held until
	 * {@see releaseDirectoryScanLock()}; the OS releases the lock if the process dies while holding it.
	 *
	 * @return resource|null
	 */
	private function acquireDirectoryScanLock(string $lockKey, int $pollIntervalMicroseconds = self::SCAN_LOCK_POLL_INTERVAL_MICROSECONDS)
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

			usleep($pollIntervalMicroseconds);
		}

		return $lockHandle;
	}

	/**
	 * @param non-empty-string $cacheKey
	 * @return array<string, array{string, string[], string[], string[]}>|null
	 */
	private function loadCachedSymbols(string $cacheKey, string $variableCacheKey): ?array
	{
		/** @var array<string, array{string, string[], string[], string[]}>|null $cached */
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
	 * @param string[] $files
	 * @param non-empty-string&literal-string $uniqueCacheIdentifier
	 */
	public function createByFiles(array $files, string $uniqueCacheIdentifier): OptimizedDirectorySourceLocator
	{
		$fileHashes = [];
		foreach ($files as $file) {
			$hash = $this->fileContentHasher->hash($file);
			if ($hash === false) {
				continue;
			}
			$fileHashes[$file] = $hash;
		}

		return $this->createCachedDirectorySourceLocator($fileHashes, $uniqueCacheIdentifier);
	}

	/**
	 * @param array<string, array{string, string[], string[], string[]}> $symbols
	 * @return array{array<string, string>, array<string, array<int, string>>, array<string, string>}
	 */
	private function changeStructure(array $symbols): array
	{
		$classToFile = [];
		$constantToFile = [];
		$functionToFiles = [];
		foreach ($symbols as $file => [, $classes, $functions, $constants]) {
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
