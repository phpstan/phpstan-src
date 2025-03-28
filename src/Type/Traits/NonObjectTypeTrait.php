<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

trait NonObjectTypeTrait
{

	public function isObject(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isEnum(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function canCallMethods(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		throw new ShouldNotHappenException();
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getConstant(string $constantName): ClassConstantReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return new ErrorType();
	}

}
