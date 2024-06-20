<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface dynamic parameter out type extensions implement for non-static methods.
 *
 * To register it in the configuration file use the `phpstan.methodParameterOutTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.methodParameterOutTypeExtension
 * ```
 *
 * @api
 */
interface MethodParameterOutTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;

	public function getParameterOutTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
