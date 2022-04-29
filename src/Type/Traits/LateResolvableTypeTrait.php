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
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Type;

trait LateResolvableTypeTrait
{

	private ?Type $result = null;

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->getResult()->accepts($type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->getResult()->isSuperTypeOf($type);
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->getResult()->canAccessConstants();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->getResult()->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->getResult()->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->getResult()->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->getResult()->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->getResult()->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): MethodReflection
	{
		return $this->getResult()->getMethod($methodName, $scope);
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		return $this->getResult()->getUnresolvedMethodPrototype($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->getResult()->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->getResult()->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->getResult()->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->getResult()->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->getResult()->isIterableAtLeastOnce();
	}

	public function getIterableKeyType(): Type
	{
		return $this->getResult()->getIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->getResult()->getIterableValueType();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->getResult()->isArray();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->getResult()->isOffsetAccessible();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->getResult()->hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->getResult()->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->getResult()->setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->getResult()->unsetOffset($offsetType);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->getResult()->isCallable();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return $this->getResult()->getCallableParametersAcceptors($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->getResult()->isCloneable();
	}

	public function toBoolean(): BooleanType
	{
		return $this->getResult()->toBoolean();
	}

	public function toNumber(): Type
	{
		return $this->getResult()->toNumber();
	}

	public function toInteger(): Type
	{
		return $this->getResult()->toInteger();
	}

	public function toFloat(): Type
	{
		return $this->getResult()->toFloat();
	}

	public function toString(): Type
	{
		return $this->getResult()->toString();
	}

	public function toArray(): Type
	{
		return $this->getResult()->toArray();
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->getResult()->isSmallerThan($otherType);
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->getResult()->isSmallerThanOrEqual($otherType);
	}

	public function isString(): TrinaryLogic
	{
		return $this->getResult()->isString();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->getResult()->isNumericString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->getResult()->isNonEmptyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->getResult()->isLiteralString();
	}

	public function getSmallerType(): Type
	{
		return $this->getResult()->getSmallerType();
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->getResult()->getSmallerOrEqualType();
	}

	public function getGreaterType(): Type
	{
		return $this->getResult()->getGreaterType();
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->getResult()->getGreaterOrEqualType();
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->getResult()->inferTemplateTypes($receivedType);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->getResult()->tryRemove($typeToRemove);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		$result = $this->getResult();

		if ($result instanceof CompoundType) {
			return $result->isSubTypeOf($otherType);
		}

		return $otherType->isSuperTypeOf($result);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$result = $this->getResult();

		if ($result instanceof CompoundType) {
			return $result->isAcceptedBy($acceptingType, $strictTypes);
		}

		return $acceptingType->accepts($result, $strictTypes);
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		$result = $this->getResult();

		if ($result instanceof CompoundType) {
			return $result->isGreaterThan($otherType);
		}

		return $otherType->isSmallerThan($result);
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		$result = $this->getResult();

		if ($result instanceof CompoundType) {
			return $result->isGreaterThanOrEqual($otherType);
		}

		return $otherType->isSmallerThanOrEqual($result);
	}

	public function resolve(): Type
	{
		if ($this->result === null) {
			return $this->result = $this->getResult();
		}

		return $this->result;
	}

	abstract private function getResult(): Type;

}
