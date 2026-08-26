<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * This is the interface for dynamic parameter type extensions for static methods.
 *
 * To register it in the configuration file use the `phpstan.dynamicStaticMethodParameterTypeExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyExtension
 *		tags:
 *			- phpstan.dynamicStaticMethodParameterTypeExtension
 * ```
 *
 * @api
 */
#[ExtensionInterface(tag: 'phpstan.dynamicStaticMethodParameterTypeExtension')]
interface DynamicStaticMethodParameterTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool;

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): ?Type;

}
