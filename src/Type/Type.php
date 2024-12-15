<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;

/**
 * @api
 * @see https://phpstan.org/developing-extensions/type-system
 */
interface Type
{

	/**
	 * @return list<string>
	 */
	public function getReferencedClasses(): array;

	/** @return list<non-empty-string> */
	public function getObjectClassNames(): array;

	/**
	 * @return list<ClassReflection>
	 */
	public function getObjectClassReflections(): array;

	/**
	 * Returns object type Foo for class-string<Foo> and 'Foo' (if Foo is a valid class).
	 */
	public function getClassStringObjectType(): Type;

	/**
	 * Returns object type Foo for class-string<Foo>, 'Foo' (if Foo is a valid class),
	 * and object type Foo.
	 */
	public function getObjectTypeOrClassStringObjectType(): Type;

	public function isObject(): TrinaryLogic;

	public function isEnum(): TrinaryLogic;

	/** @return list<ArrayType|ConstantArrayType> */
	public function getArrays(): array;

	/** @return list<ConstantArrayType> */
	public function getConstantArrays(): array;

	/** @return list<ConstantStringType> */
	public function getConstantStrings(): array;

	public function accepts(Type $type, bool $strictTypes): AcceptsResult;

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult;

	public function equals(Type $type): bool;

	public function describe(VerbosityLevel $level): string;

	public function canAccessProperties(): TrinaryLogic;

	public function hasProperty(string $propertyName): TrinaryLogic;

	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function canCallMethods(): TrinaryLogic;

	public function hasMethod(string $methodName): TrinaryLogic;

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection;

	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection;

	public function canAccessConstants(): TrinaryLogic;

	public function hasConstant(string $constantName): TrinaryLogic;

	public function getConstant(string $constantName): ClassConstantReflection;

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

	public function isOffsetAccessLegal(): TrinaryLogic;

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic;

	public function getOffsetValueType(Type $offsetType): Type;

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type;

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type;

	public function unsetOffset(Type $offsetType): Type;

	public function getKeysArray(): Type;

	public function getValuesArray(): Type;

	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type;

	public function fillKeysArray(Type $valueType): Type;

	public function flipArray(): Type;

	public function intersectKeyArray(Type $otherArraysType): Type;

	public function popArray(): Type;

	public function reverseArray(TrinaryLogic $preserveKeys): Type;

	public function searchArray(Type $needleType): Type;

	public function shiftArray(): Type;

	public function shuffleArray(): Type;

	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type;

	/**
	 * @return list<EnumCaseObjectType>
	 */
	public function getEnumCases(): array;

	/**
	 * Returns a list of finite values.
	 *
	 * Examples:
	 *
	 * - for bool: [true, false]
	 * - for int<0, 3>: [0, 1, 2, 3]
	 * - for enums: list of enum cases
	 * - for scalars: the scalar itself
	 *
	 * For infinite types it returns an empty array.
	 *
	 * @return list<Type>
	 */
	public function getFiniteTypes(): array;

	public function exponentiate(Type $exponent): Type;

	public function isCallable(): TrinaryLogic;

	/**
	 * @return CallableParametersAcceptor[]
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

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	/**
	 * Is Type of a known constant value? Includes literal strings, integers, floats, true, false, null, and array shapes.
	 */
	public function isConstantValue(): TrinaryLogic;

	/**
	 * Is Type of a known constant scalar value? Includes literal strings, integers, floats, true, false, and null.
	 */
	public function isConstantScalarValue(): TrinaryLogic;

	/**
	 * @return list<ConstantScalarType>
	 */
	public function getConstantScalarTypes(): array;

	/**
	 * @return list<int|float|string|bool|null>
	 */
	public function getConstantScalarValues(): array;

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

	public function isLowercaseString(): TrinaryLogic;

	public function isUppercaseString(): TrinaryLogic;

	public function isClassString(): TrinaryLogic;

	public function isVoid(): TrinaryLogic;

	public function isScalar(): TrinaryLogic;

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType;

	public function getSmallerType(PhpVersion $phpVersion): Type;

	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type;

	public function getGreaterType(PhpVersion $phpVersion): Type;

	public function getGreaterOrEqualType(PhpVersion $phpVersion): Type;

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
	 * @return list<TemplateTypeReference>
	 */
	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array;

	public function toAbsoluteNumber(): Type;

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
	 * Traverses inner types while keeping the same context in another type.
	 *
	 * @param callable(Type $left, Type $right): Type $cb
	 */
	public function traverseSimultaneously(Type $right, callable $cb): Type;

	public function toPhpDocNode(): TypeNode;

	/**
	 * Return the difference with another type, or null if it cannot be represented.
	 *
	 * @see TypeCombinator::remove()
	 */
	public function tryRemove(Type $typeToRemove): ?Type;

	public function generalize(GeneralizePrecision $precision): Type;

}
