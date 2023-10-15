<?php declare(strict_types = 1);

namespace PHPStan\File;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function array_filter;
use function array_values;
use function count;
use function file_exists;
use function implode;
use function is_file;
use function preg_match;
use function str_ends_with;

class FileFinder
{

	/**
	 * @param string[] $fileExtensions
	 */
	public function __construct(
		private FileExcluder $fileExcluder,
		private FileHelper $fileHelper,
		private array $fileExtensions,
	)
	{
	}

	/**
	 * @param string[] $paths
	 */
	public function findFiles(array $paths): FileFinderResult
	{
		$onlyFiles = true;
		$files = [];
		foreach ($paths as $path) {
			if (is_file($path)) {
				$files[] = $this->fileHelper->normalizePath($path);
			} elseif (!file_exists($path)) {
				throw new PathNotFoundException($path);
			} else {
				$flags = RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS | FilesystemIterator::CURRENT_AS_PATHNAME;
				$iterator = new RecursiveDirectoryIterator($path, $flags);
				$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);

				foreach ($iterator as $pathName) {
					if ($this->skipPath($pathName)) {
						continue;
					}
					$files[] = $this->fileHelper->normalizePath($pathName);
					$onlyFiles = false;
				}
			}
		}

		$files = array_values(array_filter($files, fn (string $file): bool => !$this->fileExcluder->isExcludedFromAnalysing($file)));

		return new FileFinderResult($files, $onlyFiles);
	}

	private function skipPath(string $pathName): bool
	{
		// fast path without regex for the common case
		if (count($this->fileExtensions) === 1) {
			return !str_ends_with($pathName, '.' . $this->fileExtensions[0]);
		}

		return !preg_match('{\.(' . implode('|', $this->fileExtensions) . ')$}', $pathName);
	}

}
