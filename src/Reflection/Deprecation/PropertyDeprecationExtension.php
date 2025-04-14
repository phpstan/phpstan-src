<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;

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
 *			- phpstan.propertyDeprecationExtension
 * ```
 *
 * @api
 */
interface PropertyDeprecationExtension
{

	public const PROPERTY_EXTENSION_TAG = 'phpstan.propertyDeprecationExtension';

	public function getPropertyDeprecation(ReflectionProperty $reflection): ?Deprecation;

}
