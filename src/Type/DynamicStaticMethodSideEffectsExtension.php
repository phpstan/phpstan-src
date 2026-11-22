<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\SideEffects;

/**
 * This is the interface dynamic side effects extensions implement for static methods.
 * They describe the side effects of one call, without the effects of the
 * callables passed to the parameters flagged with @pure-unless-callable-is-impure.
 *
 * To register it in the configuration file use the `phpstan.dynamicStaticMethodSideEffectsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicStaticMethodSideEffectsExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.dynamicStaticMethodSideEffectsExtension')]
interface DynamicStaticMethodSideEffectsExtension
{

	public function getClass(): string;

	public function isStaticMethodSupported(ExtendedMethodReflection $methodReflection): bool;

	/** null keeps the side effects the method reflection describes */
	public function getSideEffectsFromStaticMethodCall(ExtendedMethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?SideEffects;

}
