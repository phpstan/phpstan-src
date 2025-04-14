<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;

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
 *			- phpstan.enumCaseDeprecationExtension
 * ```
 *
 * @api
 */
interface EnumCaseDeprecationExtension
{

	public const ENUM_CASE_EXTENSION_TAG = 'phpstan.enumCaseDeprecationExtension';

	public function getEnumCaseDeprecation(ReflectionEnumUnitCase|ReflectionEnumBackedCase $reflection): ?Deprecation;

}
