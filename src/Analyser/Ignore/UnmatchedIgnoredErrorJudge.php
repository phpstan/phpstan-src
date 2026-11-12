<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\Rules\Traits\TraitDeclarationCollector;
use PHPStan\Rules\Traits\UsedTraitFilesCollector;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function rtrim;
use function str_starts_with;
use function strcspn;
use function strrpos;
use function substr;
use const DIRECTORY_SEPARATOR;

/**
 * Decides which ignoreErrors entries that matched nothing can be reported as unmatched when the analysed paths
 * do not cover all the paths from the configuration. What is not analysed reports no errors, so only the part
 * of an entry about the analysed code that is analysed entirely can be reported: not an entry without a path,
 * and not a file that uses a trait that is not analysed (the trait's errors are reported in the file),
 * or that declares a trait (the trait is analysed in the context of its users, which are not all known).
 *
 * The traits come from what the analysis already collected: the trait files analysed as a part of the classes
 * in each analysed file, and the analysed files declaring a trait. Without them, no file is taken as analysed entirely.
 *
 * Without paths in the configuration, the analysed directories are taken as the whole project.
 *
 * @phpstan-import-type ExpandedIgnoredErrorData from IgnoredErrorHelperResult
 */
final class UnmatchedIgnoredErrorJudge
{

	/** @var array<string, true> */
	private array $analysedFiles;

	private bool $partialAnalysis = false;

	/** @var array<string, true>|null */
	private ?array $traitDeclaringFiles = null;

	/** @var array<string, array<string, true>> analysed file => normalized trait files */
	private array $usedTraitFiles = [];

	/** @var array<string, bool> */
	private array $judgeablePaths = [];

	/**
	 * @param string[] $analysedFiles normalized
	 * @param string[] $analysedPaths normalized
	 * @param string[] $analysedPathsFromConfig normalized
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private FileExcluder $analyseFileExcluder,
		array $analysedFiles,
		private array $analysedPaths,
		private array $analysedPathsFromConfig,
		private ?LazyCollectedData $collectedData,
	)
	{
		$this->analysedFiles = array_fill_keys($analysedFiles, true);
		foreach ($analysedPathsFromConfig as $configPath) {
			if (!self::isUnder($configPath, $analysedPaths)) {
				$this->partialAnalysis = true;
				break;
			}
		}
	}

	/**
	 * The entry to report as unmatched, of an entry with `paths` only with the paths whose errors are known.
	 *
	 * @param string|ExpandedIgnoredErrorData $ignoreError
	 * @return string|ExpandedIgnoredErrorData|null null when it is not known whether the entry is unmatched
	 */
	public function getJudgeablePart(string|array $ignoreError): string|array|null
	{
		if (!$this->partialAnalysis) {
			return $ignoreError;
		}

		if (!is_array($ignoreError)) {
			return null;
		}

		if (isset($ignoreError['realPath'])) {
			return array_key_exists($ignoreError['realPath'], $this->analysedFiles) && $this->isEntirelyAnalysed($ignoreError['realPath']) ? $ignoreError : null;
		}

		if (isset($ignoreError['path'])) {
			return $this->canJudgePath($ignoreError['path']) ? $ignoreError : null;
		}

		if (!isset($ignoreError['paths'])) {
			return null;
		}

		foreach ($ignoreError['paths'] as $j => $path) {
			if ($this->canJudgePath($path)) {
				continue;
			}

			unset($ignoreError['paths'][$j]);
		}

		return count($ignoreError['paths']) === 0 ? null : $ignoreError;
	}

	/**
	 * A directory, a fnmatch pattern, or a path that does not exist. Entries often share one.
	 */
	private function canJudgePath(string $path): bool
	{
		$path = $this->fileHelper->normalizePath($path);

		return $this->judgeablePaths[$path] ??= $this->doCanJudgePath($path);
	}

	private function doCanJudgePath(string $path): bool
	{
		$literalPath = $path;
		if (FileExcluder::isFnmatchPattern($path)) {
			// the pattern matches only files under the directory before its first wildcard
			$separatorPosition = strrpos(substr($path, 0, strcspn($path, '*?[]')), DIRECTORY_SEPARATOR);
			if ($separatorPosition === false) {
				return false;
			}

			$literalPath = substr($path, 0, $separatorPosition);
		}

		if (!self::isUnder($literalPath, $this->analysedPaths)) {
			return false;
		}

		$fileExcluder = new FileExcluder($this->fileHelper, [$path]);
		foreach (array_keys($this->analysedFiles) as $file) {
			if (!$fileExcluder->isExcludedFromAnalysing($file)) {
				continue;
			}

			if (!$this->isEntirelyAnalysed($file)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compares whole path segments, src/Foo.php is not under srcc/.
	 *
	 * @param string[] $directories
	 */
	private static function isUnder(string $path, array $directories): bool
	{
		$path = rtrim($path, DIRECTORY_SEPARATOR);
		foreach ($directories as $directory) {
			$directory = rtrim($directory, DIRECTORY_SEPARATOR);
			if ($path === $directory || str_starts_with($path, $directory . DIRECTORY_SEPARATOR)) {
				return true;
			}
		}

		return false;
	}

	private function isEntirelyAnalysed(string $file): bool
	{
		if ($this->collectedData === null) {
			return false;
		}

		if ($this->traitDeclaringFiles === null) {
			$this->traitDeclaringFiles = [];
			foreach ($this->collectedData->iterate(TraitDeclarationCollector::class) as $declaringFile => $declarations) {
				if ($declarations === []) {
					continue;
				}

				$this->traitDeclaringFiles[$declaringFile] = true;
			}

			foreach ($this->collectedData->iterate(UsedTraitFilesCollector::class) as $usingFile => $traitFilesOfClasses) {
				foreach ($traitFilesOfClasses as $traitFiles) {
					foreach ($traitFiles as $traitFile) {
						$this->usedTraitFiles[$usingFile][$this->fileHelper->normalizePath($traitFile)] = true;
					}
				}
			}
		}

		if (array_key_exists($file, $this->traitDeclaringFiles)) {
			return false;
		}

		foreach (array_keys($this->usedTraitFiles[$file] ?? []) as $traitFile) {
			if (array_key_exists($traitFile, $this->analysedFiles)) {
				continue;
			}

			// a trait that is not analysed even when the whole project is, like one in a scanned directory, reports no errors either way
			if (self::isUnder($traitFile, $this->analysedPathsFromConfig) && !$this->analyseFileExcluder->isExcludedFromAnalysing($traitFile)) {
				return false;
			}
		}

		return true;
	}

}
