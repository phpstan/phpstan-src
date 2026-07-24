<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ExtendedMethodReflection;

/**
 * This is the extension interface to implement if you want to describe an always-used class method.
 *
 * To register it in the configuration file use the `phpstan.methods.alwaysUsedMethodExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.methods.alwaysUsedMethodExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.methods.alwaysUsedMethodExtension')]
interface AlwaysUsedMethodExtension
{

	public function isAlwaysUsed(ExtendedMethodReflection $methodReflection): bool;

}
