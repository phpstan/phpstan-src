<?php declare(strict_types = 1);

namespace PHPStan\Reflection\RequireExtension;

use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\ShouldNotHappenException;

final class RequireExtendsMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $this->findMethod($classReflection, $methodName) !== null;
	}

	/**
	 * @return ExtendedMethodReflection
	 */
	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		$method = $this->findMethod($classReflection, $methodName);
		if ($method === null) {
			throw new ShouldNotHappenException();
		}

		return $method;
	}

	/**
	 * @return ExtendedMethodReflection|null
	 */
	private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
	{
		if (!$classReflection->isInterface()) {
			return null;
		}

		$extendsTags = $classReflection->getRequireExtendsTags();
		foreach ($extendsTags as $extendsTag) {
			$type = $extendsTag->getType();

			if (!$type->hasMethod($methodName)->yes()) {
				continue;
			}

			return $type->getMethod($methodName, new OutOfClassScope());
		}

		$interfaces = $classReflection->getInterfaces();
		foreach ($interfaces as $interface) {
			$method = $this->findMethod($interface, $methodName);
			if ($method !== null) {
				return $method;
			}
		}

		return null;
	}

}
