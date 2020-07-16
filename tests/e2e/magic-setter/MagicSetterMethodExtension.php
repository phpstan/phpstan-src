<?php declare(strict_types = 1);

namespace MagicSetter;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

class MagicSetterMethodExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getName() === Foo::class && $methodName === 'setName';
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new MagicSetterMethodReflection($classReflection, $methodName);
	}

}
