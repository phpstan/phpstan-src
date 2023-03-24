<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use PHPStan\Command\AnalysisResult;
use PHPStan\Command\Output;

/**
 * This is the interface custom error formatters implement. Register it in the configuration file
 * like this:
 *
 * ```
 * services:
 * 	errorFormatter.myFormat:
 *		class: App\PHPStan\AwesomeErrorFormatter
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/error-formatters
 *
 * @api
 */
interface ErrorFormatter
{

	/**
	 * Formats the errors and outputs them to the console.
	 *
	 * @return int Error code.
	 */
	public function formatErrors(
		AnalysisResult $analysisResult,
		Output $output,
	): int;

}
