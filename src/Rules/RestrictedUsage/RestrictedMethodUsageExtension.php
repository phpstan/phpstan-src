<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ExtendedMethodReflection;

/**
 * Extensions implementing this interface are called for each analysed method call.
 *
 * Extension can decide to create RestrictedUsage object
 * with error message & error identifier to be reported for this method call.
 *
 * Typical usage is to report errors for methods marked as @-deprecated or @-internal.
 *
 * To register it in the configuration file use the following tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.restrictedMethodUsageExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: self::METHOD_EXTENSION_TAG)]
interface RestrictedMethodUsageExtension
{

	public const METHOD_EXTENSION_TAG = 'phpstan.restrictedMethodUsageExtension';

	public function isRestrictedMethodUsage(
		ExtendedMethodReflection $methodReflection,
		Scope $scope,
	): ?RestrictedUsage;

}
