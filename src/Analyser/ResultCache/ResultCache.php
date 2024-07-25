<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;

/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
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
	 * @param array<string, array<CollectedData>> $collectedData
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<RootExportedNode>> $exportedNodes
	 * @param array<string, array{string, bool, string}> $projectExtensionFiles
	 */
	public function __construct(
		private array $filesToAnalyse,
		private bool $fullAnalysis,
		private int $lastFullAnalysisTime,
		private array $meta,
		private array $errors,
		private array $locallyIgnoredErrors,
		private array $linesToIgnore,
		private array $unmatchedLineIgnores,
		private array $collectedData,
		private array $dependencies,
		private array $exportedNodes,
		private array $projectExtensionFiles,
	)
	{
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
	 * @return array<string, array<CollectedData>>
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
	 * @return array<string, array<RootExportedNode>>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	/**
	 * @return array<string, array{string, bool, string}>
	 */
	public function getProjectExtensionFiles(): array
	{
		return $this->projectExtensionFiles;
	}

}
