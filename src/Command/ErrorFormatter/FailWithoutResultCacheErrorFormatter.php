<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

class FailWithoutResultCacheErrorFormatter implements ErrorFormatter
{

	public function __construct(
		private TableErrorFormatter $tableErrorFormatter,
	)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		if (!$analysisResult->isResultCacheUsed()) {
			return 2;
		}

		return $this->tableErrorFormatter->formatErrors($analysisResult, $output);
	}

}
