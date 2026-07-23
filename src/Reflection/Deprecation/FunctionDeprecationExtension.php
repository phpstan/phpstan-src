<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\DependencyInjection\ExtensionInterface;

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
#[ExtensionInterface(tag: self::FUNCTION_EXTENSION_TAG)]
interface FunctionDeprecationExtension
{

	public const FUNCTION_EXTENSION_TAG = 'phpstan.functionDeprecationExtension';

	public function getFunctionDeprecation(ReflectionFunction $reflection): ?Deprecation;

}
