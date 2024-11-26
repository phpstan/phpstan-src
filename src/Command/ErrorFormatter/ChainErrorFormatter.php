<?php declare(strict_types=1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

/**
 * @api
 */
final class ChainErrorFormatter implements ErrorFormatter
{

	/**
	 * @param list<ErrorFormatter> $formatters
	 */
	public function __construct(
		private array $formatters,
	)
	{
	}

	public function formatErrors(AnalysisResult $analysisResult, Output $output): int
	{
		foreach ($this->formatters as $errorFormatter) {
			$errorFormatter->formatErrors($analysisResult, $output);
		}

		return $analysisResult->hasErrors() ? 1 : 0;
	}

}
