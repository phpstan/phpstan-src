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
 *			- phpstan.methodDeprecationProvider
 * ```
 *
 * @api
 */
interface MethodDeprecationProvider
{

	public function getMethodDeprecation(ReflectionMethod $reflection): ?Deprecation;

}
