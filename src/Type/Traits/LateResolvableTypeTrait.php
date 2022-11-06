<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

trait LateResolvableTypeTrait
{

	private ?Type $result = null;

	public function getArrays(): array
	{
		return $this->resolve()->getArrays();
	}

	public function getConstantArrays(): array
	{
		return $this->resolve()->getConstantArrays();
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		return $this->resolve()->accepts($type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		return $this->isSuperTypeOfDefault($type);
	}

	private function isSuperTypeOfDefault(Type $type): TrinaryLogic
	{
		if ($type instanceof NeverType) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof LateResolvableType) {
			$type = $type->resolve();
		}

		$isSuperType = $this->resolve()->isSuperTypeOf($type);

		if (!$this->isResolvable()) {
			$isSuperType = $isSuperType->and(TrinaryLogic::createMaybe());
		}

		return $isSuperType;
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->resolve()->canAccessConstants();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->resolve()->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection
	{
		return $this->resolve()->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->resolve()->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function canCallMethods(): TrinaryLogic
	{
		return $this->resolve()->canCallMethods();
	}

	public function hasMethod(string $methodName): TrinaryLogic
	{
		return $this->resolve()->hasMethod($methodName);
	}

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
	{
		return $this->resolve()->getMethod($methodName, $scope);
	}

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
	{
		return $this->resolve()->getUnresolvedMethodPrototype($methodName, $scope);
	}

	public function canAccessConstants(): TrinaryLogic
	{
		return $this->resolve()->canAccessConstants();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return $this->resolve()->hasConstant($constantName);
	}

	public function getConstant(string $constantName): ConstantReflection
	{
		return $this->resolve()->getConstant($constantName);
	}

	public function isIterable(): TrinaryLogic
	{
		return $this->resolve()->isIterable();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return $this->resolve()->isIterableAtLeastOnce();
	}

	public function getArraySize(): Type
	{
		return $this->resolve()->getArraySize();
	}

	public function getIterableKeyType(): Type
	{
		return $this->resolve()->getIterableKeyType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return $this->resolve()->getFirstIterableKeyType();
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->resolve()->getLastIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->resolve()->getIterableValueType();
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->resolve()->getFirstIterableValueType();
	}

	public function getLastIterableValueType(): Type
	{
		return $this->resolve()->getLastIterableValueType();
	}

	public function isArray(): TrinaryLogic
	{
		return $this->resolve()->isArray();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return $this->resolve()->isConstantArray();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return $this->resolve()->isOversizedArray();
	}

	public function isList(): TrinaryLogic
	{
		return $this->resolve()->isList();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return $this->resolve()->isOffsetAccessible();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $this->resolve()->hasOffsetValueType($offsetType);
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return $this->resolve()->getOffsetValueType($offsetType);
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return $this->resolve()->setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->resolve()->unsetOffset($offsetType);
	}

	public function getKeysArray(): Type
	{
		return $this->resolve()->getKeysArray();
	}

	public function getValuesArray(): Type
	{
		return $this->resolve()->getValuesArray();
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return $this->resolve()->fillKeysArray($valueType);
	}

	public function flipArray(): Type
	{
		return $this->resolve()->flipArray();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return $this->resolve()->intersectKeyArray($otherArraysType);
	}

	public function popArray(): Type
	{
		return $this->resolve()->popArray();
	}

	public function searchArray(Type $needleType): Type
	{
		return $this->resolve()->searchArray($needleType);
	}

	public function shiftArray(): Type
	{
		return $this->resolve()->shiftArray();
	}

	public function shuffleArray(): Type
	{
		return $this->resolve()->shuffleArray();
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->resolve()->isCallable();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return $this->resolve()->getCallableParametersAcceptors($scope);
	}

	public function isCloneable(): TrinaryLogic
	{
		return $this->resolve()->isCloneable();
	}

	public function toBoolean(): BooleanType
	{
		return $this->resolve()->toBoolean();
	}

	public function toNumber(): Type
	{
		return $this->resolve()->toNumber();
	}

	public function toInteger(): Type
	{
		return $this->resolve()->toInteger();
	}

	public function toFloat(): Type
	{
		return $this->resolve()->toFloat();
	}

	public function toString(): Type
	{
		return $this->resolve()->toString();
	}

	public function toArray(): Type
	{
		return $this->resolve()->toArray();
	}

	public function toArrayKey(): Type
	{
		return $this->resolve()->toArrayKey();
	}

	public function isSmallerThan(Type $otherType): TrinaryLogic
	{
		return $this->resolve()->isSmallerThan($otherType);
	}

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic
	{
		return $this->resolve()->isSmallerThanOrEqual($otherType);
	}

	public function isInteger(): TrinaryLogic
	{
		return $this->resolve()->isInteger();
	}

	public function isString(): TrinaryLogic
	{
		return $this->resolve()->isString();
	}

	public function isNumericString(): TrinaryLogic
	{
		return $this->resolve()->isNumericString();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return $this->resolve()->isNonEmptyString();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return $this->resolve()->isNonFalsyString();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return $this->resolve()->isLiteralString();
	}

	public function getSmallerType(): Type
	{
		return $this->resolve()->getSmallerType();
	}

	public function getSmallerOrEqualType(): Type
	{
		return $this->resolve()->getSmallerOrEqualType();
	}

	public function getGreaterType(): Type
	{
		return $this->resolve()->getGreaterType();
	}

	public function getGreaterOrEqualType(): Type
	{
		return $this->resolve()->getGreaterOrEqualType();
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->resolve()->inferTemplateTypes($receivedType);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->resolve()->tryRemove($typeToRemove);
	}

	public function isSubTypeOf(Type $otherType): TrinaryLogic
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isSubTypeOf($otherType);
		}

		return $otherType->isSuperTypeOf($result);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isAcceptedBy($acceptingType, $strictTypes);
		}

		return $acceptingType->accepts($result, $strictTypes);
	}

	public function isGreaterThan(Type $otherType): TrinaryLogic
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isGreaterThan($otherType);
		}

		return $otherType->isSmallerThan($result);
	}

	public function isGreaterThanOrEqual(Type $otherType): TrinaryLogic
	{
		$result = $this->resolve();

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

	abstract protected function getResult(): Type;

}
