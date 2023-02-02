<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;

/** @api */
interface Type
{

	/**
	 * @return string[]
	 */
	public function getReferencedClasses(): array;

	/** @return list<string> */
	public function getObjectClassNames(): array;

	public function isObject(): TrinaryLogic;

	/** @return list<ArrayType> */
	public function getArrays(): array;

	/** @return list<ConstantArrayType> */
	public function getConstantArrays(): array;

	/** @return list<ConstantStringType> */
	public function getConstantStrings(): array;

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic;

	/**
	 * This is like accepts() but gives reasons
	 * why the type was not/might not be accepted in some non-intuitive scenarios.
	 *
	 * In PHPStan 2.0 this method will be removed and the return type of accepts()
	 * will change to AcceptsResult.
	 */
	public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult;

	public function isSuperTypeOf(Type $type): TrinaryLogic;

	public function equals(Type $type): bool;

	public function describe(VerbosityLevel $level): string;

	public function canAccessProperties(): TrinaryLogic;

	public function hasProperty(string $propertyName): TrinaryLogic;

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): PropertyReflection;

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function canCallMethods(): TrinaryLogic;

	public function hasMethod(string $methodName): TrinaryLogic;

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection;

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection;

	public function canAccessConstants(): TrinaryLogic;

	public function hasConstant(string $constantName): TrinaryLogic;

	public function getConstant(string $constantName): ConstantReflection;

	public function isIterable(): TrinaryLogic;

	public function isIterableAtLeastOnce(): TrinaryLogic;

	public function getArraySize(): Type;

	public function getIterableKeyType(): Type;

	public function getFirstIterableKeyType(): Type;

	public function getLastIterableKeyType(): Type;

	public function getIterableValueType(): Type;

	public function getFirstIterableValueType(): Type;

	public function getLastIterableValueType(): Type;

	public function isArray(): TrinaryLogic;

	public function isConstantArray(): TrinaryLogic;

	public function isOversizedArray(): TrinaryLogic;

	public function isList(): TrinaryLogic;

	public function isOffsetAccessible(): TrinaryLogic;

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic;

	public function getOffsetValueType(Type $offsetType): Type;

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type;

	public function unsetOffset(Type $offsetType): Type;

	public function getKeysArray(): Type;

	public function getValuesArray(): Type;

	public function fillKeysArray(Type $valueType): Type;

	public function flipArray(): Type;

	public function intersectKeyArray(Type $otherArraysType): Type;

	public function popArray(): Type;

	public function searchArray(Type $needleType): Type;

	public function shiftArray(): Type;

	public function shuffleArray(): Type;

	/**
	 * @return list<EnumCaseObjectType>
	 */
	public function getEnumCases(): array;

	public function exponentiate(Type $exponent): Type;

	public function isCallable(): TrinaryLogic;

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array;

	public function isCloneable(): TrinaryLogic;

	public function toBoolean(): BooleanType;

	public function toNumber(): Type;

	public function toInteger(): Type;

	public function toFloat(): Type;

	public function toString(): Type;

	public function toArray(): Type;

	public function toArrayKey(): Type;

	public function isSmallerThan(Type $otherType): TrinaryLogic;

	public function isSmallerThanOrEqual(Type $otherType): TrinaryLogic;

	public function isNull(): TrinaryLogic;

	public function isTrue(): TrinaryLogic;

	public function isFalse(): TrinaryLogic;

	public function isBoolean(): TrinaryLogic;

	public function isFloat(): TrinaryLogic;

	public function isInteger(): TrinaryLogic;

	public function isString(): TrinaryLogic;

	public function isNumericString(): TrinaryLogic;

	public function isNonEmptyString(): TrinaryLogic;

	public function isNonFalsyString(): TrinaryLogic;

	public function isLiteralString(): TrinaryLogic;

	public function isClassStringType(): TrinaryLogic;

	public function isVoid(): TrinaryLogic;

	public function isScalar(): TrinaryLogic;

	public function getSmallerType(): Type;

	public function getSmallerOrEqualType(): Type;

	public function getGreaterType(): Type;

	public function getGreaterOrEqualType(): Type;

	/**
	 * Returns actual template type for a given object.
	 *
	 * Example:
	 *
	 * @-template T
	 * class Foo {}
	 *
	 * // $fooType is Foo<int>
	 * $t = $fooType->getTemplateType(Foo::class, 'T');
	 * $t->isInteger(); // yes
	 *
	 * Returns ErrorType in case of a missing type.
	 *
	 * @param class-string $ancestorClassName
	 */
	public function getTemplateType(string $ancestorClassName, string $templateTypeName): Type;

	/**
	 * Infers template types
	 *
	 * Infers the real Type of the TemplateTypes found in $this, based on
	 * the received Type.
	 */
	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap;

	/**
	 * Returns the template types referenced by this Type, recursively
	 *
	 * The return value is a list of TemplateTypeReferences, who contain the
	 * referenced template type as well as the variance position in which it was
	 * found.
	 *
	 * For example, calling this on array<Foo<T>,Bar> (with T a template type)
	 * will return one TemplateTypeReference for the type T.
	 *
	 * @param TemplateTypeVariance $positionVariance The variance position in
	 *                                               which the receiver type was
	 *                                               found.
	 *
	 * @return TemplateTypeReference[]
	 */
	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array;

	/**
	 * Traverses inner types
	 *
	 * Returns a new instance with all inner types mapped through $cb. Might
	 * return the same instance if inner types did not change.
	 *
	 * @param callable(Type):Type $cb
	 */
	public function traverse(callable $cb): Type;

	/**
	 * Return the difference with another type, or null if it cannot be represented.
	 *
	 * @see TypeCombinator::remove()
	 */
	public function tryRemove(Type $typeToRemove): ?Type;

	public function generalize(GeneralizePrecision $precision): Type;

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self;

}
