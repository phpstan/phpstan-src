<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\AnalyserResult;

final class ResultCacheProcessResult
{

	public function __construct(private AnalyserResult $analyserResult, private bool $saved)
	{
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
