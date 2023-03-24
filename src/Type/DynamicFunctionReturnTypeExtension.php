<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;

/**
 * This is the interface dynamic return type extensions implement for functions.
 *
 * To register it in the configuration file use the `phpstan.broker.dynamicFunctionReturnTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.broker.dynamicFunctionReturnTypeExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/dynamic-return-type-extensions
 *
 * @api
 */
interface DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool;

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type;

}
