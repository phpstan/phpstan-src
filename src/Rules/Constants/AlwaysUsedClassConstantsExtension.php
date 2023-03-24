<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Reflection\ConstantReflection;

/**
 * This is the extension interface to implement if you want to describe
 * always-used class constant.
 *
 * To register it in the configuration file use the `phpstan.constants.alwaysUsedClassConstantsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.constants.alwaysUsedClassConstantsExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/always-used-class-constants
 *
 * @api
 */
interface AlwaysUsedClassConstantsExtension
{

	public function isAlwaysUsed(ConstantReflection $constant): bool;

}
