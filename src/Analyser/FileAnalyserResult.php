<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\ExportedNode;

class FileAnalyserResult
{

	/**
	 * @param Error[] $errors
	 * @param CollectedData[] $collectedData
	 * @param array<int, string> $dependencies
	 * @param array<int, ExportedNode> $exportedNodes
	 */
	public function __construct(
		private array $errors,
		private array $collectedData,
		private array $dependencies,
		private array $exportedNodes,
	)
	{
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return CollectedData[]
	 */
	public function getCollectedData(): array
	{
		return $this->collectedData;
	}

	/**
	 * @return array<int, string>
	 */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	/**
	 * @return array<int, ExportedNode>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

}
