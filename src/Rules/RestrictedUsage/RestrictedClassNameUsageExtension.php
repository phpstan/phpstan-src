<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;

/**
 * Extensions implementing this interface are called for each analysed class name usage.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this method call.
 *
 * Typical usage is to report errors for class names marked as @-deprecated or @-internal.
 *
 * Extension can take advantage of the usage location information in the ClassNameUsageLocation object.
 *
 * To register the extension in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.restrictedClassNameUsageExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::CLASS_NAME_EXTENSION_TAG)]
interface RestrictedClassNameUsageExtension
{

	public const CLASS_NAME_EXTENSION_TAG = 'phpstan.restrictedClassNameUsageExtension';

	public function isRestrictedClassNameUsage(
		ClassReflection $classReflection,
		Scope $scope,
		ClassNameUsageLocation $location,
	): ?RestrictedUsage;

}
