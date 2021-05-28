<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

/** @api */
interface ErrorFormatter
{

	/**
	 * Formats the errors and outputs them to the console.
	 *
	 * @param \PHPStan\Command\AnalysisResult $analysisResult
	 * @param \PHPStan\Command\Output $output
	 * @return int Error code.
	 */
	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output
	): int;

}
