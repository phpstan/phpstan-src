<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface for dynamically specifying the $this context
 * for closure parameters in static method calls.
 *
 * To register it in the configuration file use the `phpstan.staticMethodParameterClosureThisExtension` service tag:
 *
 * ```
 * services:
 * 	-
 * 		class: App\PHPStan\MyExtension
 * 		tags:
 * 			- phpstan.staticMethodParameterClosureThisExtension
 * ```
 *
 * @api
 */
interface StaticMethodParameterClosureThisExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;

	public function getClosureThisTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
