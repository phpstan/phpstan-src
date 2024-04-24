<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;

/**
 * @phpstan-type LinesToIgnore = array<string, array<int, non-empty-list<string>|null>>
 */
class FileAnalyserResult
{

	/**
	 * @param list<Error> $errors
	 * @param list<Error> $filteredPhpErrors
	 * @param list<Error> $allPhpErrors
	 * @param list<Error> $locallyIgnoredErrors
	 * @param list<CollectedData> $collectedData
	 * @param list<string> $dependencies
	 * @param list<RootExportedNode> $exportedNodes
	 * @param LinesToIgnore $linesToIgnore
	 * @param LinesToIgnore $unmatchedLineIgnores
	 */
	public function __construct(
		private array $errors,
		private array $filteredPhpErrors,
		private array $allPhpErrors,
		private array $locallyIgnoredErrors,
		private array $collectedData,
		private array $dependencies,
		private array $exportedNodes,
		private array $linesToIgnore,
		private array $unmatchedLineIgnores,
	)
	{
	}

	/**
	 * @return list<Error>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return list<Error>
	 */
	public function getFilteredPhpErrors(): array
	{
		return $this->filteredPhpErrors;
	}

	/**
	 * @return list<Error>
	 */
	public function getAllPhpErrors(): array
	{
		return $this->allPhpErrors;
	}

	/**
	 * @return list<Error>
	 */
	public function getLocallyIgnoredErrors(): array
	{
		return $this->locallyIgnoredErrors;
	}

	/**
	 * @return list<CollectedData>
	 */
	public function getCollectedData(): array
	{
		return $this->collectedData;
	}

	/**
	 * @return list<string>
	 */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	/**
	 * @return list<RootExportedNode>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getLinesToIgnore(): array
	{
		return $this->linesToIgnore;
	}

	/**
	 * @return LinesToIgnore
	 */
	public function getUnmatchedLineIgnores(): array
	{
		return $this->unmatchedLineIgnores;
	}

}
