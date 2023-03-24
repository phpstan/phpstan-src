<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;

/**
 * This is the interface dynamic throw type extensions implement for functions.
 *
 * To register it in the configuration file use the `phpstan.dynamicFunctionThrowTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicFunctionThrowTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-throw-type-extensions
 *
 * @api
 */
interface DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool;

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type;

}
