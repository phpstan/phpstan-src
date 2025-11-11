<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Type;

trait LateResolvableTypeTrait
{

	private ?Type $result = null;

	public function getObjectClassNames(): array
	{
		return $this->resolve()->getObjectClassNames();
	}

	public function getObjectClassReflections(): array
	{
		return $this->resolve()->getObjectClassReflections();
	}

	public function getArrays(): array
	{
		return $this->resolve()->getArrays();
	}

	public function getConstantArrays(): array
	{
		return $this->resolve()->getConstantArrays();
	}

	public function getConstantStrings(): array
	{
		return $this->resolve()->getConstantStrings();
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return $this->resolve()->accepts($type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		return $this->isSuperTypeOfDefault($type);
	}

	private function isSuperTypeOfDefault(Type $type): IsSuperTypeOfResult
	{
		if (!$type->isNever()->no()) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type instanceof LateResolvableType) {
			$type = $type->resolve();
		}

		$isSuperType = $this->resolve()->isSuperTypeOf($type);

		if (!$this->isResolvable()) {
			$isSuperType = $isSuperType->and(IsSuperTypeOfResult::createMaybe());
		}

		return $isSuperType;
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return $this->resolve()->getTemplateType($ancestorClassName, $templateTypeName);
	}

	public function isObject(): TrinaryLogic
	{
		return $this->resolve()->isObject();
	}

	public function isEnum(): TrinaryLogic
	{
		return $this->resolve()->isEnum();
	}

	public function canAccessProperties(): TrinaryLogic
	{
		return $this->resolve()->canAccessProperties();
	}

	public function hasProperty(string $propertyName): TrinaryLogic
	{
		return $this->resolve()->hasProperty($propertyName);
	}

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->resolve()->getProperty($propertyName, $scope);
	}

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->resolve()->getUnresolvedPropertyPrototype($propertyName, $scope);
	}

	public function hasInstanceProperty(string $propertyName): TrinaryLogic
	{
		return $this->resolve()->hasInstanceProperty($propertyName);
	}

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->resolve()->getInstanceProperty($propertyName, $scope);
	}

	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->resolve()->getUnresolvedInstancePropertyPrototype($propertyName, $scope);
	}

	public function hasStaticProperty(string $propertyName): TrinaryLogic
	{
		return $this->resolve()->hasStaticProperty($propertyName);
	}

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
	{
		return $this->resolve()->getStaticProperty($propertyName, $scope);
	}

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
	{
		return $this->resolve()->getUnresolvedStaticPropertyPrototype($propertyName, $scope);
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

	public function getConstant(string $constantName): ClassConstantReflection
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
		return $this->resolve()->getIterableKeyType();
	}

	public function getLastIterableKeyType(): Type
	{
		return $this->resolve()->getIterableKeyType();
	}

	public function getIterableValueType(): Type
	{
		return $this->resolve()->getIterableValueType();
	}

	public function getFirstIterableValueType(): Type
	{
		return $this->resolve()->getIterableValueType();
	}

	public function getLastIterableValueType(): Type
	{
		return $this->resolve()->getIterableValueType();
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

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return $this->resolve()->isOffsetAccessLegal();
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

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this->resolve()->setExistingOffsetValueType($offsetType, $valueType);
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return $this->resolve()->unsetOffset($offsetType);
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->resolve()->getKeysArrayFiltered($filterValueType, $strict);
	}

	public function getKeysArray(): Type
	{
		return $this->resolve()->getKeysArray();
	}

	public function getValuesArray(): Type
	{
		return $this->resolve()->getValuesArray();
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->resolve()->chunkArray($lengthType, $preserveKeys);
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

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return $this->resolve()->reverseArray($preserveKeys);
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return $this->resolve()->searchArray($needleType, $strict);
	}

	public function shiftArray(): Type
	{
		return $this->resolve()->shiftArray();
	}

	public function shuffleArray(): Type
	{
		return $this->resolve()->shuffleArray();
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return $this->resolve()->sliceArray($offsetType, $lengthType, $preserveKeys);
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		return $this->resolve()->spliceArray($offsetType, $lengthType, $replacementType);
	}

	public function isCallable(): TrinaryLogic
	{
		return $this->resolve()->isCallable();
	}

	public function getEnumCases(): array
	{
		return $this->resolve()->getEnumCases();
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

	public function toAbsoluteNumber(): Type
	{
		return $this->resolve()->toAbsoluteNumber();
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

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this->resolve()->toCoercedArgumentType($strictTypes);
	}

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->resolve()->isSmallerThan($otherType, $phpVersion);
	}

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		return $this->resolve()->isSmallerThanOrEqual($otherType, $phpVersion);
	}

	public function isNull(): TrinaryLogic
	{
		return $this->resolve()->isNull();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return $this->resolve()->isConstantValue();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return $this->resolve()->isConstantScalarValue();
	}

	public function getConstantScalarTypes(): array
	{
		return $this->resolve()->getConstantScalarTypes();
	}

	public function getConstantScalarValues(): array
	{
		return $this->resolve()->getConstantScalarValues();
	}

	public function isTrue(): TrinaryLogic
	{
		return $this->resolve()->isTrue();
	}

	public function isFalse(): TrinaryLogic
	{
		return $this->resolve()->isFalse();
	}

	public function isBoolean(): TrinaryLogic
	{
		return $this->resolve()->isBoolean();
	}

	public function isFloat(): TrinaryLogic
	{
		return $this->resolve()->isFloat();
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

	public function isLowercaseString(): TrinaryLogic
	{
		return $this->resolve()->isLowercaseString();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return $this->resolve()->isUppercaseString();
	}

	public function isClassString(): TrinaryLogic
	{
		return $this->resolve()->isClassString();
	}

	public function getClassStringObjectType(): Type
	{
		return $this->resolve()->getClassStringObjectType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return $this->resolve()->getObjectTypeOrClassStringObjectType();
	}

	public function isVoid(): TrinaryLogic
	{
		return $this->resolve()->isVoid();
	}

	public function isScalar(): TrinaryLogic
	{
		return $this->resolve()->isScalar();
	}

	public function isNever(): TrinaryLogic
	{
		return $this->resolve()->isNever();
	}

	public function isExplicitNever(): TrinaryLogic
	{
		return $this->resolve()->isExplicitNever();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function getSmallerType(PhpVersion $phpVersion): Type
	{
		return $this->resolve()->getSmallerType($phpVersion);
	}

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->resolve()->getSmallerOrEqualType($phpVersion);
	}

	public function getGreaterType(PhpVersion $phpVersion): Type
	{
		return $this->resolve()->getGreaterType($phpVersion);
	}

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
	{
		return $this->resolve()->getGreaterOrEqualType($phpVersion);
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		return $this->resolve()->inferTemplateTypes($receivedType);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		return $this->resolve()->tryRemove($typeToRemove);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isSubTypeOf($otherType);
		}

		return $otherType->isSuperTypeOf($result);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isAcceptedBy($acceptingType, $strictTypes);
		}

		return $acceptingType->accepts($result, $strictTypes);
	}

	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isGreaterThan($otherType, $phpVersion);
		}

		return $otherType->isSmallerThan($result, $phpVersion);
	}

	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic
	{
		$result = $this->resolve();

		if ($result instanceof CompoundType) {
			return $result->isGreaterThanOrEqual($otherType, $phpVersion);
		}

		return $otherType->isSmallerThanOrEqual($result, $phpVersion);
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this->resolve()->exponentiate($exponent);
	}

	public function getFiniteTypes(): array
	{
		return $this->resolve()->getFiniteTypes();
	}

	public function resolve(): Type
	{
		return $this->result ??= $this->getResult();
	}

	abstract protected function getResult(): Type;

}
