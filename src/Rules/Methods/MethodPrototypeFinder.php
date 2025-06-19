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
	 * @return array{ExtendedMethodReflection, ClassReflection, bool}|null
	 */
	public function findPrototype(ClassReflection $classReflection, string $methodName): ?array
	{
		foreach ($classReflection->getImmediateInterfaces() as $immediateInterface) {
			if ($immediateInterface->hasNativeMethod($methodName)) {
				$method = $immediateInterface->getNativeMethod($methodName);
				return [$method, $method->getDeclaringClass(), true];
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
					return [
						$this->phpClassReflectionExtension->createUserlandMethodReflection(
							$trait,
							$classReflection,
							$methodReflection,
							$declaringTrait->getName(),
						),
						$declaringTrait,
						false,
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

		return [$method, $method->getDeclaringClass(), true];
	}

}
