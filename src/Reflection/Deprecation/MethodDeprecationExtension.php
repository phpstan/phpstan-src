<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;

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
 *			- phpstan.methodDeprecationExtension
 * ```
 *
 * @api
 */
interface MethodDeprecationExtension
{

	public const METHOD_EXTENSION_TAG = 'phpstan.methodDeprecationExtension';

	public function getMethodDeprecation(ReflectionMethod $reflection): ?Deprecation;

}
