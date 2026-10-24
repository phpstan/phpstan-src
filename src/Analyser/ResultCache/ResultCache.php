<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
use function array_fill_keys;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type CollectorData from CollectedData
 */
final class ResultCache
{

	/**
	 * @param string[] $filesToAnalyse
	 * @param mixed[] $meta
	 * @param array<string, list<Error>> $errors
	 * @param array<string, list<Error>> $locallyIgnoredErrors
	 * @param array<string, LinesToIgnore> $linesToIgnore
	 * @param array<string, LinesToIgnore> $unmatchedLineIgnores
	 * @param CollectorData $collectedData
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<string>> $usedTraitDependencies
	 * @param array<string, array{string, string, string, list<string>, list<string>}> $valueDependencies id => [extension class, key, current value, analysis dependents, declarations dependents] - see ValueDependencyCollector
	 * @param array<string, array<string>> $packageDependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes the decoded ones - see $cachedExportedNodes for the rest
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 * @param array<string, string> $currentFileHashes
	 * @param list<string> $ruleScopedFiles files in $filesToAnalyse that are re-analysed only for $changedRuleClasses
	 * @param list<string> $changedRuleClasses
	 * @param array<string, list<string>> $nodeTypes the cached node classes per analysed file
	 */
	public function __construct(
		private array $filesToAnalyse,
		private bool $fullAnalysis,
		private ?string $fullAnalysisReason,
		private int $lastFullAnalysisTime,
		private array $meta,
		private array $errors,
		private array $locallyIgnoredErrors,
		private array $linesToIgnore,
		private array $unmatchedLineIgnores,
		private array $collectedData,
		private array $dependencies,
		private array $usedTraitDependencies,
		private array $valueDependencies,
		private array $packageDependencies,
		private array $exportedNodes,
		private CachedExportedNodes $cachedExportedNodes,
		private array $projectExtensionFiles,
		private array $currentFileHashes,
		private array $ruleScopedFiles = [],
		private array $changedRuleClasses = [],
		private array $nodeTypes = [],
	)
	{
	}

	/**
	 * @return list<string>
	 */
	public function getRuleScopedFiles(): array
	{
		return $this->ruleScopedFiles;
	}

	/**
	 * @return list<string>
	 */
	public function getChangedRuleClasses(): array
	{
		return $this->changedRuleClasses;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public function getNodeTypes(): array
	{
		return $this->nodeTypes;
	}

	/**
	 * The same cache with only the files that are re-analysed with all rules, for the merges that
	 * replace everything of a re-analysed file.
	 */
	public function withoutRuleScopedFiles(): self
	{
		if ($this->ruleScopedFiles === []) {
			return $this;
		}

		$ruleScoped = array_fill_keys($this->ruleScopedFiles, true);
		$filesToAnalyse = [];
		foreach ($this->filesToAnalyse as $file) {
			if (isset($ruleScoped[$file])) {
				continue;
			}

			$filesToAnalyse[] = $file;
		}

		return new self(
			$filesToAnalyse,
			$this->fullAnalysis,
			$this->fullAnalysisReason,
			$this->lastFullAnalysisTime,
			$this->meta,
			$this->errors,
			$this->locallyIgnoredErrors,
			$this->linesToIgnore,
			$this->unmatchedLineIgnores,
			$this->collectedData,
			$this->dependencies,
			$this->usedTraitDependencies,
			$this->valueDependencies,
			$this->packageDependencies,
			$this->exportedNodes,
			$this->cachedExportedNodes,
			$this->projectExtensionFiles,
			$this->currentFileHashes,
			[],
			$this->changedRuleClasses,
			$this->nodeTypes,
		);
	}

	/**
	 * @return string[]
	 */
	public function getFilesToAnalyse(): array
	{
		return $this->filesToAnalyse;
	}

	public function isFullAnalysis(): bool
	{
		return $this->fullAnalysis;
	}

	/**
	 * Human-readable explanation of why the cached results could not be reused,
	 * null when the result cache is being used.
	 */
	public function getFullAnalysisReason(): ?string
	{
		return $this->fullAnalysisReason;
	}

	public function getLastFullAnalysisTime(): int
	{
		return $this->lastFullAnalysisTime;
	}

	/**
	 * @return mixed[]
	 */
	public function getMeta(): array
	{
		return $this->meta;
	}

	/**
	 * @return array<string, list<Error>>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return array<string, list<Error>>
	 */
	public function getLocallyIgnoredErrors(): array
	{
		return $this->locallyIgnoredErrors;
	}

	/**
	 * @return array<string, LinesToIgnore>
	 */
	public function getLinesToIgnore(): array
	{
		return $this->linesToIgnore;
	}

	/**
	 * @return array<string, LinesToIgnore>
	 */
	public function getUnmatchedLineIgnores(): array
	{
		return $this->unmatchedLineIgnores;
	}

	/**
	 * @return CollectorData
	 */
	public function getCollectedData(): array
	{
		return $this->collectedData;
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getUsedTraitDependencies(): array
	{
		return $this->usedTraitDependencies;
	}

	/**
	 * @return array<string, array{string, string, string, list<string>, list<string>}>
	 */
	public function getValueDependencies(): array
	{
		return $this->valueDependencies;
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getPackageDependencies(): array
	{
		return $this->packageDependencies;
	}

	/**
	 * @return array<string, array<RootExportedNode>>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	/**
	 * The exported nodes carried over from the cache file undecoded, for the files not in getExportedNodes().
	 */
	public function getCachedExportedNodes(): CachedExportedNodes
	{
		return $this->cachedExportedNodes;
	}

	/**
	 * @return array<string, array{string, bool, string}>
	 */
	public function getProjectExtensionFiles(): array
	{
		return $this->projectExtensionFiles;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCurrentFileHashes(): array
	{
		return $this->currentFileHashes;
	}

}
