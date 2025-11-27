<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileFinder;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConstantNameHelper;
use function array_key_exists;
use function count;
use function in_array;
use function ltrim;
use function php_strip_whitespace;
use function preg_match_all;
use function preg_replace;
use function sha1_file;
use function sprintf;
use function strtolower;

#[AutowiredService]
final class OptimizedDirectorySourceLocatorFactory
{

	private PhpFileCleaner $cleaner;

	private string $extraTypes;

	public function __construct(
		private FileNodesFetcher $fileNodesFetcher,
		#[AutowiredParameter(ref: '@fileFinderScan')]
		private FileFinder $fileFinder,
		private PhpVersion $phpVersion,
		private Cache $cache,
	)
	{
		$this->extraTypes = $this->phpVersion->supportsEnums() ? '|enum' : '';
		$this->cleaner = new PhpFileCleaner();
	}

	public function createByDirectory(string $directory): OptimizedDirectorySourceLocator
	{
		$files = $this->fileFinder->findFiles([$directory])->getFiles();
		$fileHashes = [];
		foreach ($files as $file) {
			$hash = sha1_file($file);
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
		$variableCacheKey = 'v1';

		/** @var array<string, array{string, string[], string[], string[]}>|null $cached */
		$cached = $this->cache->load($cacheKey, $variableCacheKey);
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

				[$newClasses, $newFunctions, $newConstants] = $this->findSymbols($file);
				$cached[$file] = [$newHash, $newClasses, $newFunctions, $newConstants];
			}
		} else {
			$cached = [];
		}

		foreach ($fileHashes as $file => $newHash) {
			[$newClasses, $newFunctions, $newConstants] = $this->findSymbols($file);
			$cached[$file] = [$newHash, $newClasses, $newFunctions, $newConstants];
		}
		$this->cache->save($cacheKey, $variableCacheKey, $cached);

		[$classToFile, $functionToFiles, $constantToFile] = $this->changeStructure($cached);

		return new OptimizedDirectorySourceLocator(
			$this->fileNodesFetcher,
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
			$hash = sha1_file($file);
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

	/**
	 * Inspired by Composer\Autoload\ClassMapGenerator::findClasses()
	 * @link https://github.com/composer/composer/blob/45d3e133a4691eccb12e9cd6f9dfd76eddc1906d/src/Composer/Autoload/ClassMapGenerator.php#L216
	 *
	 * @return array{string[], string[], string[]}
	 */
	private function findSymbols(string $file): array
	{
		$contents = @php_strip_whitespace($file);
		if ($contents === '') {
			return [[], [], []];
		}

		$matchResults = (bool) preg_match_all(sprintf('{\b(?:(?:class|interface|trait|const|function%s)\s)|(?:define\s*\()}i', $this->extraTypes), $contents, $matches);
		if (!$matchResults) {
			return [[], [], []];
		}

		$contents = $this->cleaner->clean($contents, count($matches[0]));

		preg_match_all(sprintf('{
			(?:
				\b(?<![\$:>])(?:
					(?: (?P<type>class|interface|trait%s) \s++ (?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) )
					| (?: (?P<function>function) \s++ (?:&\s*)? (?P<fname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [&\(] )
					| (?: (?P<constant>const) \s++ (?P<cname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\-]*+) \s*+ [^;] )
					| (?: (?:\\\)? (?P<define>define) \s*+ \( \s*+ [\'"] (?P<dname>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:[\\\\]{1,2}[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+) )
					| (?: (?P<ns>namespace) (?P<nsname>\s++[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+(?:\s*+\\\\\s*+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*+)*+)? \s*+ [\{;] )
				)
			)
		}ix', $this->extraTypes), $contents, $matches);

		$classes = [];
		$functions = [];
		$constants = [];
		$namespace = '';

		for ($i = 0, $len = count($matches['type']); $i < $len; $i++) {
			if (isset($matches['ns'][$i]) && $matches['ns'][$i] !== '') {
				$namespace = preg_replace('~\s+~', '', strtolower($matches['nsname'][$i])) . '\\';
				continue;
			}

			if ($matches['function'][$i] !== '') {
				$functions[] = strtolower(ltrim($namespace . $matches['fname'][$i], '\\'));
				continue;
			}

			if ($matches['constant'][$i] !== '') {
				$constants[] = ConstantNameHelper::normalize(ltrim($namespace . $matches['cname'][$i], '\\'));
			}

			if ($matches['define'][$i] !== '') {
				$constants[] = ConstantNameHelper::normalize($matches['dname'][$i]);
				continue;
			}

			$name = $matches['name'][$i];

			// skip anon classes extending/implementing
			if (in_array($name, ['extends', 'implements'], true)) {
				continue;
			}

			$classes[] = strtolower(ltrim($namespace . $name, '\\'));
		}

		return [
			$classes,
			$functions,
			$constants,
		];
	}

}
