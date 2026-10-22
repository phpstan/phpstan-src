<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;

#[AutowiredService]
final class ParentMethodHelper
{

	public function __construct(
		private PhpClassReflectionExtension $phpClassReflectionExtension,
	)
	{
	}

	/**
	 * @return list<array{ExtendedMethodReflection, ClassReflection}>
	 */
	public function collectParentMethods(string $methodName, ClassReflection $class): array
	{
		$parentMethods = [];

		$parentClass = $class->getParentClass();
		if ($parentClass !== null && $parentClass->hasNativeMethod($methodName)) {
			$parentMethod = $parentClass->getNativeMethod($methodName);
			if (!$parentMethod->isPrivate()) {
				$parentMethods[] = [$parentMethod, $parentMethod->getDeclaringClass()];
			}
		}

		foreach ($class->getInterfaces() as $interface) {
			if (!$interface->hasNativeMethod($methodName)) {
				continue;
			}

			$method = $interface->getNativeMethod($methodName);
			$parentMethods[] = [$method, $method->getDeclaringClass()];
		}

		foreach ($class->getTraits(true) as $trait) {
			$nativeTraitReflection = $trait->getNativeReflection();
			if (!$nativeTraitReflection->hasMethod($methodName)) {
				continue;
			}

			$methodReflection = $nativeTraitReflection->getMethod($methodName);
			$isAbstract = $methodReflection->isAbstract();
			if (!$isAbstract) {
				continue;
			}

			// Skip traits that inherited the method from a sub-trait
			// The actual declaring trait will be processed separately
			if ($methodReflection->getBetterReflection()->getDeclaringClass()->getName() !== $trait->getName()) {
				continue;
			}

			$declaringTrait = $trait->getNativeMethod($methodName)->getDeclaringClass();
			$parentMethods[] = [
				$this->phpClassReflectionExtension->createUserlandMethodReflection(
					$trait,
					$class,
					$methodReflection,
					$declaringTrait->getName(),
				),
				$declaringTrait,
			];
		}

		return $parentMethods;
	}

}
