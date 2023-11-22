<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;

/**
 * This is the interface dynamic return type extensions implement for static methods.
 *
 * To register it in the configuration file use the `phpstan.broker.dynamicStaticMethodReturnTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.dynamicStaticMethodReturnTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-return-type-extensions
 *
 * @api
 */
interface DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): ?string;

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool;

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type;

}
