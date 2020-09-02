<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Dependency\ExportedNode;

class FileAnalyserResult
{

	/** @var Error[] */
	private array $errors;

	/** @var array<int, string> */
	private array $dependencies;

	/** @var array<int, ExportedNode> */
	private array $exportedNodes;

	/**
	 * @param Error[] $errors
	 * @param array<int, string> $dependencies
	 * @param array<int, ExportedNode> $exportedNodes
	 */
	public function __construct(array $errors, array $dependencies, array $exportedNodes)
	{
		$this->errors = $errors;
		$this->dependencies = $dependencies;
		$this->exportedNodes = $exportedNodes;
	}

	/**
	 * @return Error[]
	 */
	public function getErrors(): array
	{
		return $this->errors;
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
