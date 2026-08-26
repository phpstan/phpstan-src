<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface for dynamic parameter type extensions for methods.
 *
 * To register it in the configuration file use the `phpstan.dynamicMethodParameterTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicMethodParameterTypeExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.dynamicMethodParameterTypeExtension')]
interface DynamicMethodParameterTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
