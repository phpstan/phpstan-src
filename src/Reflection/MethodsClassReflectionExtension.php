<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\ExtensionInterface;

/**
 * This is the interface custom methods class reflection extensions implement.
 *
 * To register it in the configuration file use the `phpstan.broker.methodsClassReflectionExtension` service tag:
 *
 * ```
 * services:
 * 	-
 *		class: App\PHPStan\MyMethodsClassReflectionExtension
 *		tags:
 *			- phpstan.broker.methodsClassReflectionExtension
 * ```
 *
 * Learn more: https://phpstan.org/developing-extensions/class-reflection-extensions
 *
 * @api
 */
#[ExtensionInterface(tag: BrokerFactory::METHODS_CLASS_REFLECTION_EXTENSION_TAG)]
interface MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool;

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection;

}
