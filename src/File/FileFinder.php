<?php declare(strict_types = 1);

namespace PHPStan\File;

use SplFileInfo;
// use Symfony\Component\Finder\Finder;
use function array_filter;
use function array_unique;
use function array_values;
use function file_exists;
use function implode;
use function is_file;

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

				continue;
			} elseif (!file_exists($path)) {
				throw new PathNotFoundException($path);
			}

			$finder = (new FileFinderSymfonyBackport())
				->in($path)
				->followLinks()
				->filter(fn (SplFileInfo $fileInfo) => !$this->fileExcluder->isExcludedFromAnalysing($fileInfo->getPathname()), true)
				->files()
				->name('*.{' . implode(',', $this->fileExtensions) . '}');

			foreach ($finder as $fileInfo) {
				$files[] = $this->fileHelper->normalizePath($fileInfo->getPathname());
				$onlyFiles = false;
			}
		}

		$files = array_values(array_unique(array_filter($files, fn (string $file): bool => !$this->fileExcluder->isExcludedFromAnalysing($file))));

		return new FileFinderResult($files, $onlyFiles);
	}

}
