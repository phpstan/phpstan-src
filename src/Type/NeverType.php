<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
class NeverType implements CompoundType
{

	use UndecidedBooleanTypeTrait;
	use NonGenericTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonRemoveableTypeTrait;
	use NonGeneralizableTypeTrait;

	/** @api */
	public function __construct(private bool $isExplicit = false)
	{
	}

	public function isExplicit(): bool
	{
		return $this->isExplicit;
	}

	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getArrays(): array
	{
		return [];
	}

	public function getConstantArrays(): array
	{
		return [];
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return AcceptsResult::createYes();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createYes();
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		return IsSuperTypeOfResult::createYes();
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function describe(VerbosityLevel $level): string
	{
		return '*NEVER*';
	}

	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type
	{
		return new NeverType();
	}

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
		return TrinaryLogic::createYes();
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
		return TrinaryLogic::createYes();
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
		return TrinaryLogic::createYes();
	}

	public function hasConstant(string $constantName): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getConstant(string $constantName): ClassConstantReflection
	{
		throw new ShouldNotHappenException();
	}

	public function isIterable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isIterableAtLeastOnce(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getArraySize(): Type
	{
		return new NeverType();
	}

	public function getIterableKeyType(): Type
	{
		return new NeverType();
	}

	public function getFirstIterableKeyType(): Type
	{
		return new NeverType();
	}

	public function getLastIterableKeyType(): Type
	{
		return new NeverType();
	}

	public function getIterableValueType(): Type
	{
		return new NeverType();
	}

	public function getFirstIterableValueType(): Type
	{
		return new NeverType();
	}

	public function getLastIterableValueType(): Type
	{
		return new NeverType();
	}

	public function isArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isOversizedArray(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isList(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		return new NeverType();
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		return new ErrorType();
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return new ErrorType();
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return new NeverType();
	}

	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type
	{
		return $this->getKeysArray();
	}

	public function getKeysArray(): Type
	{
		return new NeverType();
	}

	public function getValuesArray(): Type
	{
		return new NeverType();
	}

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return new NeverType();
	}

	public function fillKeysArray(Type $valueType): Type
	{
		return new NeverType();
	}

	public function flipArray(): Type
	{
		return new NeverType();
	}

	public function intersectKeyArray(Type $otherArraysType): Type
	{
		return new NeverType();
	}

	public function popArray(): Type
	{
		return new NeverType();
	}

	public function reverseArray(TrinaryLogic $preserveKeys): Type
	{
		return new NeverType();
	}

	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type
	{
		return new NeverType();
	}

	public function shiftArray(): Type
	{
		return new NeverType();
	}

	public function shuffleArray(): Type
	{
		return new NeverType();
	}

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
	{
		return new NeverType();
	}

	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type
	{
		return new NeverType();
	}

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		throw new ShouldNotHappenException();
	}

	public function isCloneable(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function toNumber(): Type
	{
		return $this;
	}

	public function toAbsoluteNumber(): Type
	{
		return $this;
	}

	public function toString(): Type
	{
		return $this;
	}

	public function toInteger(): Type
	{
		return $this;
	}

	public function toFloat(): Type
	{
		return $this;
	}

	public function toArray(): Type
	{
		return $this;
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this;
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getConstantScalarTypes(): array
	{
		return [];
	}

	public function getConstantScalarValues(): array
	{
		return [];
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFloat(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isLowercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isClassString(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ErrorType();
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		return new BooleanType();
	}

	public function getEnumCases(): array
	{
		return [];
	}

	public function exponentiate(Type $exponent): Type
	{
		return $this;
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('never');
	}

}
