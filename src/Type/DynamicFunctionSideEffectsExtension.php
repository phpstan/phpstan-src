<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\SideEffects;

/**
 * This is the interface dynamic side effects extensions implement for functions.
 * They describe the side effects of one call, such as a function that prints
 * only for some arguments. They do not include the effects of the callables
 * passed to the parameters flagged with @pure-unless-callable-is-impure, the
 * analysis adds those.
 *
 * To register it in the configuration file use the `phpstan.dynamicFunctionSideEffectsExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicFunctionSideEffectsExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.dynamicFunctionSideEffectsExtension')]
interface DynamicFunctionSideEffectsExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool;

	/** null keeps the side effects the function reflection describes */
	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects;

}
