<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\SideEffects;

/**
 * This is the interface dynamic side effects extensions implement for methods.
 * They describe the side effects of one call, without the effects of the
 * callables passed to the parameters flagged with @pure-unless-callable-is-impure.
 *
 * The returned side effects replace the ones of the reflection for this call:
 * whether it writes the object or class it is called on, which global state
 * it forgets, whether its result is remembered, and whether it is pure. Like
 * a functionMetadata entry, the call forgets global state only as far as it
 * lists writes-global or a part of it. The arguments it writes are still the
 * ones of the reflection: they are decided while the arguments are processed.
 *
 * To register it in the configuration file use the `phpstan.dynamicMethodSideEffectsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicMethodSideEffectsExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.dynamicMethodSideEffectsExtension')]
interface DynamicMethodSideEffectsExtension
{

	public function getClass(): string;

	public function isMethodSupported(ExtendedMethodReflection $methodReflection): bool;

	/** null keeps the side effects the method reflection describes */
	public function getSideEffectsFromMethodCall(ExtendedMethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?SideEffects;

}
