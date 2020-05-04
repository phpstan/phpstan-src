<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Mixin;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

class MixinMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->findMethod($classReflection, $methodName) !== null;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$method = $this->findMethod($classReflection, $methodName);
		if ($method === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $method;
	}

	private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
	{
		$mixinTypes = $classReflection->getResolvedMixinTypes();
		foreach ($mixinTypes as $type) {
			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			return $type->getMethod($methodName, new OutOfClassScope());
		}

		foreach ($classReflection->getParents() as $parentClass) {
			$method = $this->findMethod($parentClass, $methodName);
			if ($method === null) {
				continue;
			}

			return $method;
		}

		return null;
	}

}
