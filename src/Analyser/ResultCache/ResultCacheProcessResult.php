<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\AnalyserResult;

class ResultCacheProcessResult
{

	private AnalyserResult $analyserResult;

	private bool $saved;

	public function __construct(AnalyserResult $analyserResult, bool $saved)
	{
		$this->analyserResult = $analyserResult;
		$this->saved = $saved;
	}

	public function getAnalyserResult(): AnalyserResult
	{
		return $this->analyserResult;
	}

	public function isSaved(): bool
	{
		return $this->saved;
	}

}
