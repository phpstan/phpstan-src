<?php declare(strict_types = 1);

namespace PHPStan\File;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function file_exists;
use function is_file;
use function sort;

final class FileFinder
{

	/**
	 * @param string[] $fileExtensions
	 */
	public function __construct(
		private FileExcluder $fileExcluder,
		private FileHelper $fileHelper,
		private array $fileExtensions,
		private DirectoryWalker $directoryWalker,
	)
	{
	}

	/**
	 * @param string[] $paths
	 */
	public function findFiles(array $paths): FileFinderResult
	{
		return $this->doFindFiles($paths, false);
	}

	/**
	 * Like findFiles(), but the directory walk is shared with the other FileFinder - see
	 * DirectoryWalker. Only for the callers that run once per analysis, before it starts.
	 *
	 * @param string[] $paths
	 */
	public function findFilesCached(array $paths): FileFinderResult
	{
		return $this->doFindFiles($paths, true);
	}

	/**
	 * @param string[] $paths
	 */
	private function doFindFiles(array $paths, bool $cached): FileFinderResult
	{
		$onlyFiles = true;
		$files = [];
		foreach ($paths as $path) {
			if (is_file($path)) {
				$files[] = $path;
			} elseif (!file_exists($path)) {
				throw new PathNotFoundException($path);
			} else {
				$walkedFiles = $cached
					? $this->directoryWalker->walkCached($path, $this->fileExtensions)
					: $this->directoryWalker->walk($path, $this->fileExtensions);
				foreach ($walkedFiles as $walkedFile) {
					$files[] = $walkedFile;
					$onlyFiles = false;
				}
			}
		}

		$files = array_filter($files, fn (string $file): bool => !$this->fileExcluder->isExcludedFromAnalysing($file));

		sort($files);

		return new FileFinderResult(
			array_values(array_unique(array_map(fn (string $file): string => $this->fileHelper->normalizePath($file), $files))),
			$onlyFiles,
		);
	}

}
