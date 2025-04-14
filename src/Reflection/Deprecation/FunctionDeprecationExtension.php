<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;

/**
 * This interface allows you to provide custom deprecation information
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyProvider
 *		tags:
 *			- phpstan.functionDeprecationExtension
 * ```
 *
 * @api
 */
interface FunctionDeprecationExtension
{

	public const FUNCTION_EXTENSION_TAG = 'phpstan.functionDeprecationExtension';

	public function getFunctionDeprecation(ReflectionFunction $reflection): ?Deprecation;

}
