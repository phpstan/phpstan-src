<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\Dummy\DummyConstantReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\CallbackUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

trait MaybeObjectTypeTrait
{

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return new MixedType();
	}

	public function isObject(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isEnum(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		$property = new DummyPropertyReflection();
		return new CallbackUnresolvedPropertyPrototypeReflection(
			$property,
			$property->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		$method = new DummyMethodReflection($methodName);
		return new CallbackUnresolvedMethodPrototypeReflection(
			$method,
			$method->getDeclaringClass(),
			false,
			static fn (Type $type): Type => $type,
		);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return new DummyConstantReflection($constantName);
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

}
