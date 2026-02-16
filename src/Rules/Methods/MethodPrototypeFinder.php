<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodPrototypeReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\Php\PhpMethodReflection;
use function is_bool;
use function strtolower;

#[AutowiredService]
final class MethodPrototypeFinder
{

	public function __construct(
		private PhpVersion $phpVersion,
		private PhpClassReflectionExtension $phpClassReflectionExtension,
	)
	{
	}

	/**
	 * Finds the prototype method that a class method should be validated against.
	 * Returns two prototypes with different purposes:
	 * - Signature prototype: Used for validating method signature (parameters, return type, ...).
	 * - Inheritance prototype: Used for validating inheritance rules (final keyword, override attribute, ...).
	 *
	 * @return array{ExtendedMethodReflection, ClassReflection, bool, ExtendedMethodReflection, ClassReflection}|null
	 */
	public function findPrototype(ClassReflection $classReflection, string $methodName): ?array
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasNativeMethod($methodName)) {
				$method = $immediateInterface->getNativeMethod($methodName);
				return [$method, $method->getDeclaringClass(), true, $method, $method->getDeclaringClass()];
			}
		}

		if ($this->phpVersion->supportsAbstractTraitMethods()) {
			foreach ($classReflection->getTraits(true) as $trait) {
				$nativeTraitReflection = $trait->getNativeReflection();
				if (!$nativeTraitReflection->hasMethod($methodName)) {
					continue;
				}

				$methodReflection = $nativeTraitReflection->getMethod($methodName);
				$isAbstract = $methodReflection->isAbstract();
				if ($isAbstract) {
					$declaringTrait = $trait->getNativeMethod($methodName)->getDeclaringClass();
					$prototype = $this->phpClassReflectionExtension->createUserlandMethodReflection(
						$trait,
						$classReflection,
						$methodReflection,
						$declaringTrait->getName(),
					);

					return [
						$prototype,
						$declaringTrait,
						false,
						$prototype,
						$declaringTrait,
					];
				}
			}
		}

		$parentClass = $classReflection->getParentClass();
		if ($parentClass === null) {
			return null;
		}

		if (!$parentClass->hasNativeMethod($methodName)) {
			return null;
		}

		$method = $parentClass->getNativeMethod($methodName);
		if ($method->isPrivate()) {
			return null;
		}

		$declaringClass = $method->getDeclaringClass();
		if ($declaringClass->hasConstructor()) {
			if ($method->getName() === $declaringClass->getConstructor()->getName()) {
				$prototype = $method->getPrototype();
				if ($prototype instanceof PhpMethodReflection || $prototype instanceof MethodPrototypeReflection || $prototype instanceof NativeMethodReflection) {
					$abstract = $prototype->isAbstract();
					if (is_bool($abstract)) {
						if (!$abstract) {
							return null;
						}
					} elseif (!$abstract->yes()) {
						return null;
					}
				}
			} elseif (strtolower($methodName) === '__construct') {
				return null;
			}
		}

		$prototype = $method;
		if (strtolower($method->getName()) === '__construct') {
			foreach ($parentClass->getInterfaces() as $interface) {
				if ($interface->hasNativeMethod($method->getName())) {
					$prototype = $interface->getNativeMethod($method->getName());
				}
			}
		}

		return [
			$prototype,
			$prototype->getDeclaringClass(),
			true,
			$method,
			$declaringClass,
		];
	}

}
