<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\File\RelativePathHelper;

final class BaselineIgnoredErrorHelper
{

	public function __construct(
		private FileHelper $fileHelper,
	)
	{
	}

	/**
	 * @param mixed[][] $baselinedErrors errors currently present in the baseline
	 * @param list<Error> $currentAnalysisErrors errors from the current analysis
	 * @return list<Error> errors from the current analysis which already exit in the baseline
	 */
	public function removeUnusedIgnoredErrors(array $baselinedErrors, array $currentAnalysisErrors, ParentDirectoryRelativePathHelper $baselinePathHelper): array
	{
		$ignoreErrorsByFile = $this->mapIgnoredErrorsByFile($baselinedErrors);

		$ignoreUseCount = [];
		$nextBaselinedErrors = [];
		foreach ($currentAnalysisErrors as $error) {
			$hasMatchingIgnore = $this->checkIgnoreErrorByPath($error->getFilePath(), $ignoreErrorsByFile, $error, $ignoreUseCount, $baselinePathHelper);
			if ($hasMatchingIgnore) {
				$nextBaselinedErrors[] = $error;
				continue;
			}

			$traitFilePath = $error->getTraitFilePath();
			if ($traitFilePath === null) {
				continue;
			}

			$hasMatchingIgnore = $this->checkIgnoreErrorByPath($traitFilePath, $ignoreErrorsByFile, $error, $ignoreUseCount, $baselinePathHelper);
			if (!$hasMatchingIgnore) {
				continue;
			}

			$nextBaselinedErrors[] = $error;
		}

		return $nextBaselinedErrors;
	}

	/**
	 * @param mixed[][] $ignoreErrorsByFile
	 * @param int[] $ignoreUseCount map of indexes of ignores and how often they have been "used" to ignore an error
	 */
	private function checkIgnoreErrorByPath(string $filePath, array $ignoreErrorsByFile, Error $error, array &$ignoreUseCount, RelativePathHelper $baselinePathHelper): bool
	{
		$relativePath = $baselinePathHelper->getRelativePath($filePath);
		if (!isset($ignoreErrorsByFile[$relativePath])) {
			return false;
		}

		foreach ($ignoreErrorsByFile[$relativePath] as $ignoreError) {
			$ignore = $ignoreError['ignoreError'];
			$shouldIgnore = IgnoredError::shouldIgnore($this->fileHelper, $error, $ignore['message'] ?? null, $ignore['identifier'] ?? null, null);
			if (!$shouldIgnore) {
				continue;
			}

			$realCount = $ignoreUseCount[$ignoreError['index']] ?? 0;
			$realCount++;
			$ignoreUseCount[$ignoreError['index']] = $realCount;

			if ($realCount <= $ignore['count']) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed[][] $baselineIgnoreErrors
	 * @return mixed[][] ignored errors from baseline mapped and grouped by files
	 */
	private function mapIgnoredErrorsByFile(array $baselineIgnoreErrors): array
	{
		$ignoreErrorsByFile = [];

		foreach ($baselineIgnoreErrors as $i => $ignoreError) {
			$ignoreErrorEntry = [
				'index' => $i,
				'ignoreError' => $ignoreError,
			];

			$normalizedPath = $this->fileHelper->normalizePath($ignoreError['path']);
			$ignoreErrorsByFile[$normalizedPath][] = $ignoreErrorEntry;
		}

		return $ignoreErrorsByFile;
	}

}
