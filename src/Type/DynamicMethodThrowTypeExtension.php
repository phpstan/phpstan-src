<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

/**
 * This is the interface dynamic throw type extensions implement for non-static methods.
 *
 * To register it in the configuration file use the `phpstan.dynamicMethodThrowTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicMethodThrowTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-throw-type-extensions
 *
 * @api
 */
interface DynamicMethodThrowTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection): bool;

	public function getThrowTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type;

}
