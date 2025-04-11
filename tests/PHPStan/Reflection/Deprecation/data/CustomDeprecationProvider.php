<?php // lint >= 8.0

declare(strict_types = 1);

namespace PHPStan\Tests;

use CustomDeprecations\CustomDeprecated;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\Reflection\Deprecation\ClassConstantDeprecationProvider;
use PHPStan\Reflection\Deprecation\ClassDeprecationProvider;
use PHPStan\Reflection\Deprecation\ConstantDeprecationProvider;
use PHPStan\Reflection\Deprecation\Deprecation;
use PHPStan\Reflection\Deprecation\FunctionDeprecationProvider;
use PHPStan\Reflection\Deprecation\MethodDeprecationProvider;
use PHPStan\Reflection\Deprecation\PropertyDeprecationProvider;

class CustomDeprecationProvider implements
	ConstantDeprecationProvider,
	ClassDeprecationProvider,
	ClassConstantDeprecationProvider,
	MethodDeprecationProvider,
	PropertyDeprecationProvider,
	FunctionDeprecationProvider
{

	public function getClassDeprecation(ReflectionClass|ReflectionEnum $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	public function getConstantDeprecation(ReflectionConstant $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	public function getFunctionDeprecation(ReflectionFunction $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	public function getMethodDeprecation(ReflectionMethod $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	public function getPropertyDeprecation(ReflectionProperty $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	public function getClassConstantDeprecation(ReflectionClassConstant $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	private function buildDeprecation($reflection): ?Deprecation
	{
		foreach ($reflection->getAttributes(CustomDeprecated::class) as $attribute) {
			return Deprecation::create()->withDescription($attribute->getArguments()[0] ?? $attribute->getArguments()['description'] ?? null);
		}

		return null;
	}

}
