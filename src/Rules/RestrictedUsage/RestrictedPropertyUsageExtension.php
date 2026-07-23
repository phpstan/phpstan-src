<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ExtendedPropertyReflection;

/**
 * Extensions implementing this interface are called for each analysed property access.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this property access.
 *
 * Typical usage is to report errors for properties marked as @-deprecated or @-internal.
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.restrictedPropertyUsageExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::PROPERTY_EXTENSION_TAG)]
interface RestrictedPropertyUsageExtension
{

	public const PROPERTY_EXTENSION_TAG = 'phpstan.restrictedPropertyUsageExtension';

	public function isRestrictedPropertyUsage(
		ExtendedPropertyReflection $propertyReflection,
		Scope $scope,
	): ?RestrictedUsage;

}
