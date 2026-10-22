<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyConstructorReflection;
use PHPStan\Reflection\ExtendedMethodReflection;

#[AutowiredService]
final class ConsistentConstructorHelper
{

	public function findConsistentConstructor(ClassReflection $classReflection): ?ExtendedMethodReflection
	{
		if ($classReflection->hasConsistentConstructor()) {
			if ($classReflection->hasConstructor()) {
				return $classReflection->getConstructor();
			}

			return new DummyConstructorReflection($classReflection);
		}

		$parent = $classReflection->getParentClass();
		if ($parent === null) {
			return null;
		}

		return $this->findConsistentConstructor($parent);
	}

}
