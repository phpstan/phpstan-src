<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileFinder;
use PHPStan\Internal\FileHashing;
use PHPStan\Php\PhpVersion;
use function array_key_exists;
use function array_keys;
use function hash_file;
use function sprintf;

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
	)
	{
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		$files = $this->fileFinder->findFiles([$directory])->getFiles();
		$fileHashes = [];
		foreach ($files as $file) {
			$hash = hash_file(FileHashing::ALGORITHM, $file);
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
	 * @param string[] $files
	 * @param non-empty-string&literal-string $uniqueCacheIdentifier
	 */
	public function createByFiles(array $files, string $uniqueCacheIdentifier): OptimizedDirectorySourceLocator
	{
		$fileHashes = [];
		foreach ($files as $file) {
			$hash = hash_file(FileHashing::ALGORITHM, $file);
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
