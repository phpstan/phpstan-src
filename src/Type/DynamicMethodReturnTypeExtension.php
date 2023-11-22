<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

/**
 * This is the interface dynamic return type extensions implement for non-static methods.
 *
 * To register it in the configuration file use the `phpstan.broker.dynamicMethodReturnTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.dynamicMethodReturnTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-return-type-extensions
 *
 * @api
 */
interface DynamicMethodReturnTypeExtension
{

	public function getClass(): ?string;

	public function isMethodSupported(MethodReflection $methodReflection): bool;

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type;

}
