<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\DependencyInjection\Type\LazyParameterClosureThisExtensionProvider;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface for dynamically specifying the $this context
 * for closure parameters in method calls.
 *
 * To register it in the configuration file use the `phpstan.methodParameterClosureThisExtension` service tag:
 *
 * ```
 * services:
 * 	-
 * 		class: App\PHPStan\MyExtension
 * 		tags:
 * 			- phpstan.methodParameterClosureThisExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: LazyParameterClosureThisExtensionProvider::METHOD_TAG)]
interface MethodParameterClosureThisExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;

	public function getClosureThisTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
