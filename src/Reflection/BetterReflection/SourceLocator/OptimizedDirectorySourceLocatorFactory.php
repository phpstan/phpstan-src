<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileFinder;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_keys;
use function fclose;
use function flock;
use function fopen;
use function hash_file;
use function sha1;
use function sprintf;
use const LOCK_EX;
use const LOCK_UN;

#[AutowiredService]
final class OptimizedDirectorySourceLocatorFactory
{

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $fileFinder,
		private PhpVersion $phpVersion,
		private SymbolFinderInFiles $symbolFinderInFiles,
		private Cache $cache,
		#[AutowiredParameter]
		private string $tmpDir,
	)
	{
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		$files = $this->fileFinder->findFiles([$directory])->getFiles();
		$fileHashes = [];
		foreach ($files as $file) {
			$hash = hash_file('sha256', $file);
			if ($hash === false) {
				continue;
			}
			$fileHashes[$file] = $hash;
		}

		$cacheKey = sprintf('odsl-%s', $directory);
		return $this->createCachedDirectorySourceLocator($fileHashes, $cacheKey);
	}

	/**
	 * @param array<string, string> $fileHashes
	 * @param non-empty-string $cacheKey
	 */
	private function createCachedDirectorySourceLocator(array $fileHashes, string $cacheKey): OptimizedDirectorySourceLocator
	{
		$variableCacheKey = sprintf('v1-%s', $this->phpVersion->supportsEnums() ? 'enums' : 'no-enums');

		$originalFileHashes = $fileHashes;

		/** @var array<string, array{string, string[], string[], string[]}>|null $cached */
		$cached = $this->cache->load($cacheKey, $variableCacheKey);

		$scanLock = null;
		if ($cached === null) {
			// On a cold cache every parallel worker builds the same directory locator at once and
			// scans the same directory redundantly. A scan is not published until it finishes and the
			// save is atomic, so these races are wasteful rather than unsafe. Let the first worker take
			// an exclusive lock and scan; the others block until it releases and then re-read the cache
			// it wrote, turning N redundant scans of a directory into one.
			$scanLock = $this->acquireDirectoryScanLock($cacheKey . $variableCacheKey);
			if ($scanLock !== null) {
				$cached = $this->cache->load($cacheKey, $variableCacheKey);
			}
		}

		try {
			$findInFiles = [];
			if ($cached !== null) {
				foreach ($cached as $file => [$hash, $classes, $functions, $constants]) {
					if (!array_key_exists($file, $fileHashes)) {
						unset($cached[$file]);
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
				$cached = [];
			}

			foreach (array_keys($fileHashes) as $file) {
				$findInFiles[] = $file;
			}

			foreach ($this->symbolFinderInFiles->findSymbols($findInFiles, $this->phpVersion->supportsEnums()) as $file => [$newClasses, $newFunctions, $newConstants]) {
				$newHash = $originalFileHashes[$file];
				$cached[$file] = [$newHash, $newClasses, $newFunctions, $newConstants];
			}

			$this->cache->save($cacheKey, $variableCacheKey, $cached);
		} finally {
			// Release even if scanning or saving throws, so a failing worker cannot leave other
			// workers blocked on the lock until it exits.
			if ($scanLock !== null) {
				$this->releaseDirectoryScanLock($scanLock);
			}
		}

		[$classToFile, $functionToFiles, $constantToFile] = $this->changeStructure($cached);

		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
			$this->cache,
			$this->phpVersion,
			$classToFile,
			$functionToFiles,
			$constantToFile,
		);
	}

	/**
	 * Take an exclusive cross-process lock for a directory's symbol scan so that on a cold cache only
	 * the first worker scans it. Best effort: a null return means locking is unavailable (the lock
	 * directory could not be created or the platform refused the lock) and the caller scans as before.
	 * The returned handle is held until {@see releaseDirectoryScanLock()}; the OS releases the lock if
	 * the process dies while holding it.
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

		$lockHandle = @fopen(sprintf('%s/odsl-%s.lock', $lockDirectory, sha1($lockKey)), 'c');
		if ($lockHandle === false) {
			return null;
		}

		if (!@flock($lockHandle, LOCK_EX)) {
			@fclose($lockHandle);
			return null;
		}

		return $lockHandle;
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
			$hash = hash_file('sha256', $file);
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
