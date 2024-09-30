<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\ExtendedPropertyReflection;

/**
 * This is the extension interface to implement if you want to describe
 * always-read or always-written properties.
 *
 * To register it in the configuration file use the `phpstan.properties.readWriteExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.properties.readWriteExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/always-read-written-properties
 *
 * @api
 */
interface ReadWritePropertiesExtension
{

	public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool;

	public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool;

	public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool;

}
