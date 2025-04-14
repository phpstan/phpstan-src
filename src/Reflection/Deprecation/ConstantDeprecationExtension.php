<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\ReflectionConstant;

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
 *			- phpstan.constantDeprecationExtension
 * ```
 *
 * @api
 */
interface ConstantDeprecationExtension
{

	public const CONSTANT_EXTENSION_TAG = 'phpstan.constantDeprecationExtension';

	public function getConstantDeprecation(ReflectionConstant $reflection): ?Deprecation;

}
