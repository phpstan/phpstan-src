<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;

class ResultCache
{

	/** @var bool */
	private $fullAnalysis;

	/** @var string[] */
	private $filesToAnalyse;

	/** @var int */
	private $lastFullAnalysisTime;

	/** @var array<string, array<Error>> */
	private $errors;

	/** @var array<string, array<string>> */
	private $dependencies;

	/**
	 * @param string[] $filesToAnalyse
	 * @param bool $fullAnalysis
	 * @param int $lastFullAnalysisTime
	 * @param array<string, array<Error>> $errors
	 * @param array<string, array<string>> $dependencies
	 */
	public function __construct(
		array $filesToAnalyse,
		bool $fullAnalysis,
		int $lastFullAnalysisTime,
		array $errors,
		array $dependencies
	)
	{
		$this->filesToAnalyse = $filesToAnalyse;
		$this->fullAnalysis = $fullAnalysis;
		$this->lastFullAnalysisTime = $lastFullAnalysisTime;
		$this->errors = $errors;
		$this->dependencies = $dependencies;
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

}
