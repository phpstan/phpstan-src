<?php // lint >= 8.0

declare(strict_types = 1);

namespace PHPStan\Tests;

use CustomDeprecations\CustomDeprecated;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\Reflection\Deprecation\ClassConstantDeprecationExtension;
use PHPStan\Reflection\Deprecation\ClassDeprecationExtension;
use PHPStan\Reflection\Deprecation\ConstantDeprecationExtension;
use PHPStan\Reflection\Deprecation\Deprecation;
use PHPStan\Reflection\Deprecation\EnumCaseDeprecationExtension;
use PHPStan\Reflection\Deprecation\FunctionDeprecationExtension;
use PHPStan\Reflection\Deprecation\MethodDeprecationExtension;
use PHPStan\Reflection\Deprecation\PropertyDeprecationExtension;

class CustomDeprecationExtension implements
	ConstantDeprecationExtension,
	ClassDeprecationExtension,
	ClassConstantDeprecationExtension,
	MethodDeprecationExtension,
	PropertyDeprecationExtension,
	FunctionDeprecationExtension,
	EnumCaseDeprecationExtension
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

	public function getEnumCaseDeprecation(ReflectionEnumBackedCase|ReflectionEnumUnitCase $reflection): ?Deprecation
	{
		return $this->buildDeprecation($reflection);
	}

	private function buildDeprecation($reflection): ?Deprecation
	{
		foreach ($reflection->getAttributes(CustomDeprecated::class) as $attribute) {
			$description = $attribute->getArguments()[0] ?? $attribute->getArguments()['description'] ?? null;
			return $description === null
				? Deprecation::create()
				: Deprecation::createWithDescription($description);
		}

		return null;
	}
}
