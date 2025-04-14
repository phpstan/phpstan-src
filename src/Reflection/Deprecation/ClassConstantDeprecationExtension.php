<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;

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
 *			- phpstan.classConstantDeprecationExtension
 * ```
 *
 * @api
 */
interface ClassConstantDeprecationExtension
{

	public function getClassConstantDeprecation(ReflectionClassConstant $reflection): ?Deprecation;

}
