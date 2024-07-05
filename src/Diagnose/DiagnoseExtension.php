<?php declare(strict_types = 1);

namespace PHPStan\Diagnose;

use PHPStan\Command\Output;

/**
 * DiagnoseExtension can output any diagnostic information to stderr after analysis.
 *
 * PHPStan displays this information when running the "analyse" command with "-vvv" CLI option.
 *
 * To register it in the configuration file use the `phpstan.diagnoseExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.diagnoseExtension
 * ```
 *
 * @api
 */
interface DiagnoseExtension
{

	public const EXTENSION_TAG = 'phpstan.diagnoseExtension';

	public function print(Output $errorOutput): void;

}
