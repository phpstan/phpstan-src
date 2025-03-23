<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php\Soap;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

final class SoapClientMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->is('SoapClient');
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new SoapClientMethodReflection($classReflection, $methodName);
	}

}
