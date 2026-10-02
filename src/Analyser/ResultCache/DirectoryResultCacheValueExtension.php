<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use FilesystemIterator;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;
use function count;
use function explode;
use function fnmatch;
use function hash;
use function implode;
use function is_dir;
use function ksort;
use function str_replace;
use function strlen;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * The value behind DependencyTracker::trackDirectoryDependency(): the files in a directory, recursively,
 * whose names match a pattern, with the hashes of their contents - or that the directory does not
 * exist. A file created, changed, deleted or renamed there re-analyses the files depending on it.
 *
 * The key is the directory and the pattern separated by a NUL byte. The directory is stored relative
 * to the same directory as the other paths in the result cache.
 */
#[AutowiredService]
final class DirectoryResultCacheValueExtension implements ResultCacheValueExtension
{

	private const MISSING_DIRECTORY = 'missing';

	private ResultCachePathTransformer $pathTransformer;

	public function __construct(
		private FileContentHasher $fileContentHasher,
		#[AutowiredParameter(ref: '%rootDir%')]
		string $anchorDirectory,
	)
	{
		$this->pathTransformer = new ResultCachePathTransformer($anchorDirectory);
	}

	public static function createKey(string $directory, string $pattern): string
	{
		return $directory . "\0" . $pattern;
	}

	public function getValue(string $key): string
	{
		[$directory, $pattern] = self::splitKey($key);
		if (!is_dir($directory)) {
			return self::MISSING_DIRECTORY;
		}

		$files = [];
		try {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
			foreach ($iterator as $file) {
				if (!$file instanceof SplFileInfo || !$file->isFile() || !fnmatch($pattern, $file->getFilename())) {
					continue;
				}

				$pathname = $file->getPathname();
				$files[str_replace(DIRECTORY_SEPARATOR, '/', substr($pathname, strlen($directory) + 1))] = $this->fileContentHasher->hash($pathname);
			}
		} catch (UnexpectedValueException) {
			return self::MISSING_DIRECTORY;
		}

		ksort($files);

		$lines = [];
		foreach ($files as $relativePath => $contentHash) {
			// a file deleted between listing and hashing hashes to false, which is not a file's hash
			$lines[] = $relativePath . "\0" . ($contentHash === false ? self::MISSING_DIRECTORY : $contentHash);
		}

		return hash('sha256', implode("\n", $lines));
	}

	public function keyToResultCache(string $key): string
	{
		[$directory, $pattern] = self::splitKey($key);

		return self::createKey($this->pathTransformer->relativizePath($directory), $pattern);
	}

	public function keyFromResultCache(string $storedKey): string
	{
		[$directory, $pattern] = self::splitKey($storedKey);

		return self::createKey($this->pathTransformer->absolutizePath($directory), $pattern);
	}

	/**
	 * @return array{string, string}
	 */
	private static function splitKey(string $key): array
	{
		$parts = explode("\0", $key, 2);
		if (count($parts) !== 2) {
			return [$key, '*'];
		}

		return [$parts[0], $parts[1]];
	}

}
