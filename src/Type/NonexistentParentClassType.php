<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;

class NonexistentParentClassType implements Type
{

	use JustNullableTypeTrait;
	use NonArrayTypeTrait;
	use NonCallableTypeTrait;
	use NonIterableTypeTrait;
	use NonOffsetAccessibleTypeTrait;
	use TruthyBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	public function describe(VerbosityLevel $level): string
	{
		return 'parent';
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return new ErrorType();
	}

	public function isObject(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		throw new ShouldNotHappenException();
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
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

	public function getConstant(string $constantName): ConstantReflection
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

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toString(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new ErrorType();
	}

	public function toFloat(): Type
	{
		return new ErrorType();
	}

	public function toArray(): Type
	{
		return new ErrorType();
	}

	public function toArrayKey(): Type
	{
		return new ErrorType();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function exponentiate(Type $exponent): Type
	{
		return new ErrorType();
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
