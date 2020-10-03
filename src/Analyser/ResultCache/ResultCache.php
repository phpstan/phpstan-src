<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Dependency\ExportedNode;

class ResultCache
{

	private bool $fullAnalysis;

	/** @var string[] */
	private array $filesToAnalyse;

	private int $lastFullAnalysisTime;

	/** @var array<string, array<Error>> */
	private array $errors;

	/** @var array<string, array<string>> */
	private array $dependencies;

	/** @var array<string, array<ExportedNode>> */
	private array $exportedNodes;

	/**
	 * @param string[] $filesToAnalyse
	 * @param bool $fullAnalysis
	 * @param int $lastFullAnalysisTime
	 * @param array<string, array<Error>> $errors
	 * @param array<string, array<string>> $dependencies
	 * @param array<string, array<ExportedNode>> $exportedNodes
	 */
	public function __construct(
		array $filesToAnalyse,
		bool $fullAnalysis,
		int $lastFullAnalysisTime,
		array $errors,
		array $dependencies,
		array $exportedNodes
	)
	{
		$this->filesToAnalyse = $filesToAnalyse;
		$this->fullAnalysis = $fullAnalysis;
		$this->lastFullAnalysisTime = $lastFullAnalysisTime;
		$this->errors = $errors;
		$this->dependencies = $dependencies;
		$this->exportedNodes = $exportedNodes;
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
	 * @return array<string, array<Error>>
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getDependencies(): array
	{
		return $this->dependencies;
	}

	/**
	 * @return array<string, array<ExportedNode>>
	 */
	public function getExportedNodes(): array
	{
		return $this->exportedNodes;
	}

}
