<?php declare(strict_types = 1);

namespace PHPStan\File;

use Symfony\Component\Finder\Finder;
use function array_filter;
use function array_unique;
use function array_values;
use function file_exists;
use function implode;
use function is_file;
use function str_starts_with;
use function strlen;
use function substr;

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
	 * Make relative directory prune pattern for Symfony Finder.
	 *
	 * https://github.com/symfony/symfony/blob/v6.2.12/src/Symfony/Component/Finder/Iterator/ExcludeDirectoryFilterIterator.php#L51
	 * https://github.com/symfony/symfony/blob/v6.2.12/src/Symfony/Component/Finder/Iterator/ExcludeDirectoryFilterIterator.php#L70
	 */
	private function tryToMakeFinderExcludePattern(string $excludePath, string $inPath): ?string
	{
		$excludePath = $this->fileHelper->normalizePath($excludePath, '/');
		$inPath = $this->fileHelper->normalizePath($inPath, '/');

		if ($excludePath === $inPath || str_starts_with($inPath . '/', $excludePath)) {
			return '.+';
		} if (str_starts_with($excludePath, $inPath . '/')) {
			return substr($excludePath, strlen($inPath) + 1);
		}

		return null;
	}

	/**
	 * @return list<string>
	 */
	private function makeFinderExcludePatterns(string $inPath): array
	{
		$res = [];
		foreach ($this->fileExcluder->getExcludedLiteralPaths() as $excludePath) {
			$excludePattern = $this->tryToMakeFinderExcludePattern($excludePath, $inPath);
			if ($excludePattern === null) {
				continue;
			}

			$res[] = $excludePattern;
		}

		return $res;
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

			$finder = (new Finder())
				->in($path)
				->exclude($this->makeFinderExcludePatterns($path))
				->followLinks()
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
