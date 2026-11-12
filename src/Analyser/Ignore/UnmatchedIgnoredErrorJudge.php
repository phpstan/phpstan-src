<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PHPStan\Analyser\AnalysedFileDependencyCollector;
use PHPStan\Analyser\ResultCache\LazyCollectedData;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use PHPStan\Rules\Traits\TraitDeclarationCollector;
use function array_fill_keys;
use function array_key_exists;
use function array_keys;
use function array_pop;
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
 * not a file that declares a trait (the trait is analysed in the context of its users, which are not all known),
 * and not a file what is reported in depends on a file that is not analysed, directly or through other files -
 * a trait used by a class in the file, or the declaration of a function whose purity decides whether a call
 * is reported, see AnalysedFileDependencyCollector.
 *
 * Both come from the collected data: the analysed files declaring a trait, and the dependencies tracked while
 * the files were analysed. Without them, no file is taken as analysed entirely.
 *
 * Without paths in the configuration, the analysed directories are taken as the whole project, but analysed files
 * are only a part of an unknown one.
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

	/** @var array<string, array<string, true>> analysed file => normalized files what is reported in it depends on */
	private array $analysedFileDependencies = [];

	/** @var array<string, true> analysed files what is reported in depends on the whole project */
	private array $projectDependentFiles = [];

	/** @var array<string, bool> */
	private array $entirelyAnalysedFiles = [];

	/** @var array<string, bool> */
	private array $judgeablePaths = [];

	/**
	 * @param string[] $analysedFiles normalized
	 * @param string[] $analysedPaths normalized
	 * @param string[] $analysedPathsFromConfig normalized
	 * @param bool $onlyFiles all the analysed paths are files
	 */
	public function __construct(
		private FileHelper $fileHelper,
		private FileExcluder $analyseFileExcluder,
		array $analysedFiles,
		private array $analysedPaths,
		private array $analysedPathsFromConfig,
		private ?LazyCollectedData $collectedData,
		bool $onlyFiles,
	)
	{
		$this->analysedFiles = array_fill_keys($analysedFiles, true);
		$this->partialAnalysis = $onlyFiles && $analysedPathsFromConfig === [];
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

			foreach ($this->collectedData->iterate(AnalysedFileDependencyCollector::class) as $dependentFile => $dependencies) {
				foreach ($dependencies as $dependency) {
					if ($dependency === null) {
						$this->projectDependentFiles[$dependentFile] = true;
						continue;
					}

					$this->analysedFileDependencies[$dependentFile][$this->fileHelper->normalizePath($dependency)] = true;
				}
			}
		}

		if (array_key_exists($file, $this->traitDeclaringFiles)) {
			return false;
		}

		return $this->entirelyAnalysedFiles[$file] ??= $this->areDependenciesAnalysed($file);
	}

	private function areDependenciesAnalysed(string $file): bool
	{
		$visited = [$file => true];
		$queue = [$file];
		while ($queue !== []) {
			$current = array_pop($queue);
			if ($current !== $file && array_key_exists($current, $this->entirelyAnalysedFiles)) {
				// its dependencies were already followed
				if ($this->entirelyAnalysedFiles[$current]) {
					continue;
				}

				return false;
			}

			if (array_key_exists($current, $this->projectDependentFiles)) {
				return false;
			}

			foreach (array_keys($this->analysedFileDependencies[$current] ?? []) as $dependency) {
				if (array_key_exists($dependency, $visited)) {
					continue;
				}

				$visited[$dependency] = true;
				if (array_key_exists($dependency, $this->analysedFiles)) {
					$queue[] = $dependency;
					continue;
				}

				// a file that is not analysed even when the whole project is, like one in vendor, reports no errors either way;
				// without paths in the configuration, the project is not known and every file counts
				if (
					($this->analysedPathsFromConfig === [] || self::isUnder($dependency, $this->analysedPathsFromConfig))
					&& !$this->analyseFileExcluder->isExcludedFromAnalysing($dependency)
				) {
					return false;
				}
			}
		}

		return true;
	}

}
