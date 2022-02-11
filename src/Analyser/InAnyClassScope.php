<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;

class InAnyClassScope implements ClassMemberAccessAnswerer
{

	/** @api */
	public function __construct()
	{
	}

	public function isInClass(): bool
	{
		return true;
	}

	public function getClassReflection(): ?ClassReflection
	{
		return null;
	}

	public function canAccessProperty(PropertyReflection $propertyReflection): bool
	{
		return $propertyReflection->isPublic();
	}

	public function canCallMethod(MethodReflection $methodReflection): bool
	{
		return $methodReflection->isPublic();
	}

	public function canAccessConstant(ConstantReflection $constantReflection): bool
	{
		return $constantReflection->isPublic();
	}

}
