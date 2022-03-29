<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

trait ConditionalTypeTrait
{

	private Type $result;

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->result->accepts($type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->result->isSuperTypeOf($type);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->result->canAccessConstants();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->result->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->result->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->result->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->result->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->result->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->result->getMethod($methodName, $scope);
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		return $this->result->getUnresolvedMethodPrototype($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->result->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->result->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->result->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->result->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->result->isIterableAtLeastOnce();
	}

	public function getIterableKeyType(): Type
	{
		return $this->result->getIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->result->getIterableValueType();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->result->isArray();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->result->isOffsetAccessible();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->result->hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->result->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->result->setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->result->unsetOffset($offsetType);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->result->isCallable();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return $this->result->getCallableParametersAcceptors($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->result->isCloneable();
	}

	public function toBoolean(): BooleanType
	{
		return $this->result->toBoolean();
	}

	public function toNumber(): Type
	{
		return $this->result->toNumber();
	}

	public function toInteger(): Type
	{
		return $this->result->toInteger();
	}

	public function toFloat(): Type
	{
		return $this->result->toFloat();
	}

	public function toString(): Type
	{
		return $this->result->toString();
	}

	public function toArray(): Type
	{
		return $this->result->toArray();
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->result->isSmallerThan($otherType);
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->result->isSmallerThanOrEqual($otherType);
	}

	public function isString(): TrinaryLogic
	{
		return $this->result->isString();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->result->isNumericString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->result->isNonEmptyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->result->isLiteralString();
	}

	public function getSmallerType(): Type
	{
		return $this->result->getSmallerType();
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->result->getSmallerOrEqualType();
	}

	public function getGreaterType(): Type
	{
		return $this->result->getGreaterType();
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->result->getGreaterOrEqualType();
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->result->inferTemplateTypes($receivedType);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->result->tryRemove($typeToRemove);
	}

}
