<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
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
 *			- phpstan.classConstantDeprecationExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::CLASS_CONSTANT_EXTENSION_TAG)]
interface ClassConstantDeprecationExtension
{

	public const CLASS_CONSTANT_EXTENSION_TAG = 'phpstan.classConstantDeprecationExtension';

	public function getClassConstantDeprecation(ReflectionClassConstant $reflection): ?Deprecation;

}
