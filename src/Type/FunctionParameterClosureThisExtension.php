<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface for dynamically specifying the $this context
 * for closure parameters in function calls.
 *
 * To register it in the configuration file use the `phpstan.functionParameterClosureThisExtension` service tag:
 *
 * ```
 * services:
 * 	-
 * 		class: App\PHPStan\MyExtension
 * 		tags:
 * 			- phpstan.functionParameterClosureThisExtension
 * ```
 *
 * @api
 */
interface FunctionParameterClosureThisExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool;

	public function getClosureThisTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
