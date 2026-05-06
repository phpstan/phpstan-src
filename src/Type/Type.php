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
 * Represents a PHPStan type in the type system.
 *
 * This is the central interface of PHPStan's type system. Every type that PHPStan
 * can reason about implements this interface — from simple scalars like StringType
 * to complex generics like GenericObjectType.
 *
 * Each Type knows what it accepts, what is a supertype of it, what properties/methods/constants
 * it has, what operations it supports, and how to describe itself for error messages.
 *
 * Important: Never use `instanceof` to check types. For example, `$type instanceof StringType`
 * will miss union types, intersection types with accessory types, and other composite forms.
 * Always use the `is*()` methods or `isSuperTypeOf()` instead:
 *
 *     // Wrong:
 *     if ($type instanceof StringType) { ... }
 *
 *     // Correct:
 *     if ($type->isString()->yes()) { ... }
 *
 * @api
 * @api-do-not-implement
 * @see https://phpstan.org/developing-extensions/type-system
 */
interface Type
{

	/**
	 * Returns all class names referenced anywhere in this type, recursively
	 * (including generic arguments, callable signatures, etc.).
	 *
	 * @see Type::getObjectClassNames() for only direct object type class names
	 *
	 * @return list<non-empty-string>
	 */
	public function getReferencedClasses(): array;

	/**
	 * Returns class names of the object types this type directly represents.
	 * Unlike getReferencedClasses(), excludes classes in generic arguments, etc.
	 *
	 * @return list<non-empty-string>
	 */
	public function getObjectClassNames(): array;

	/** @return list<ClassReflection> */
	public function getObjectClassReflections(): array;

	/**
	 * Return class-string<Foo> for object type Foo.
	 */
	public function getClassStringType(): Type;

	/**
	 * Returns the object type for a class-string or literal class name string.
	 * For non-class-string types, returns ErrorType.
	 */
	public function getClassStringObjectType(): Type;

	/**
	 * Like getClassStringObjectType(), but also returns object types as-is.
	 * Used for `$classOrObject::method()` where the left side can be either.
	 */
	public function getObjectTypeOrClassStringObjectType(): Type;

	public function isObject(): TrinaryLogic;

	public function isEnum(): TrinaryLogic;

	/** @return list<ArrayType|ConstantArrayType> */
	public function getArrays(): array;

	/**
	 * Only ConstantArrayType instances (array shapes with known keys).
	 *
	 * @return list<ConstantArrayType>
	 */
	public function getConstantArrays(): array;

	/** @return list<ConstantStringType> */
	public function getConstantStrings(): array;

	/**
	 * Unlike isSuperTypeOf(), accepts() takes into account PHP's implicit type coercion.
	 * With $strictTypes = false, int is accepted by float, and Stringable objects are
	 * accepted by string.
	 */
	public function accepts(Type $type, bool $strictTypes): AcceptsResult;

	/**
	 * "Does every value of $type belong to $this type?"
	 *
	 * Preferable to instanceof checks because it correctly handles
	 * union types, intersection types, and all other composite types.
	 */
	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult;

	public function equals(Type $type): bool;

	public function describe(VerbosityLevel $level): string;

	public function canAccessProperties(): TrinaryLogic;

	/** @deprecated Use hasInstanceProperty or hasStaticProperty instead */
	public function hasProperty(string $propertyName): TrinaryLogic;

	/** @deprecated Use getInstanceProperty or getStaticProperty instead */
	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	/** @deprecated Use getUnresolvedInstancePropertyPrototype or getUnresolvedStaticPropertyPrototype instead */
	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function hasInstanceProperty(string $propertyName): TrinaryLogic;

	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	/**
	 * Unlike getInstanceProperty(), this defers template type resolution.
	 * Use getInstanceProperty() in most rule implementations.
	 */
	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function hasStaticProperty(string $propertyName): TrinaryLogic;

	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	public function canCallMethods(): TrinaryLogic;

	public function hasMethod(string $methodName): TrinaryLogic;

	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection;

	/**
	 * Unlike getMethod(), this defers template type and static type resolution.
	 * Use getMethod() in most rule implementations.
	 */
	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection;

	public function canAccessConstants(): TrinaryLogic;

	public function hasConstant(string $constantName): TrinaryLogic;

	public function getConstant(string $constantName): ClassConstantReflection;

	public function isIterable(): TrinaryLogic;

	public function isIterableAtLeastOnce(): TrinaryLogic;

	/**
	 * Returns the count of elements as a Type (typically IntegerRangeType).
	 */
	public function getArraySize(): Type;

	/**
	 * Works for both arrays and Traversable objects.
	 */
	public function getIterableKeyType(): Type;

	/** @deprecated use getIterableKeyType */
	public function getFirstIterableKeyType(): Type;

	/** @deprecated use getIterableKeyType */
	public function getLastIterableKeyType(): Type;

	public function getIterableValueType(): Type;

	/** @deprecated use getIterableValueType */
	public function getFirstIterableValueType(): Type;

	/** @deprecated use getIterableValueType */
	public function getLastIterableValueType(): Type;

	public function isArray(): TrinaryLogic;

	public function isConstantArray(): TrinaryLogic;

	/**
	 * An oversized array is a constant array shape that grew too large to track
	 * precisely and was degraded to a generic array type.
	 */
	public function isOversizedArray(): TrinaryLogic;

	/**
	 * A list is an array with sequential integer keys starting from 0 with no gaps.
	 */
	public function isList(): TrinaryLogic;

	public function isOffsetAccessible(): TrinaryLogic;

	/**
	 * Whether accessing a non-existent offset is safe (won't cause errors).
	 * Unlike isOffsetAccessible() which checks if offset access is supported at all.
	 */
	public function isOffsetAccessLegal(): TrinaryLogic;

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic;

	public function getOffsetValueType(Type $offsetType): Type;

	/**
	 * May add a new key. When $offsetType is null, appends (like $a[] = $value).
	 *
	 * @see Type::setExistingOffsetValueType() for modifying an existing key without widening
	 */
	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type;

	/**
	 * Unlike setOffsetValueType(), assumes the key already exists.
	 * Preserves the array shape and list type.
	 */
	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type;

	public function unsetOffset(Type $offsetType): Type;

	/** Models array_keys($array, $searchValue, $strict). */
	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type;

	/** Models array_keys($array). */
	public function getKeysArray(): Type;

	/** Models array_values($array). */
	public function getValuesArray(): Type;

	/** Models array_chunk($array, $length, $preserveKeys). */
	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type;

	/** Models array_fill_keys($keys, $value). */
	public function fillKeysArray(Type $valueType): Type;

	/** Models array_flip($array). */
	public function flipArray(): Type;

	/** Models array_intersect_key($array, ...$otherArrays). */
	public function intersectKeyArray(Type $otherArraysType): Type;

	/** Models array_pop() effect on the array. */
	public function popArray(): Type;

	/** Models array_reverse($array, $preserveKeys). */
	public function reverseArray(TrinaryLogic $preserveKeys): Type;

	/** Models array_search($needle, $array, $strict). */
	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type;

	/** Models array_shift() effect on the array. */
	public function shiftArray(): Type;

	/** Models shuffle() effect on the array. Result is always a list. */
	public function shuffleArray(): Type;

	/** Models array_slice($array, $offset, $length, $preserveKeys). */
	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type;

	/** Models array_splice() effect on the array (the modified array, not the removed portion). */
	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type;

	/**
	 * Downgrades the list-ness of the array from `Yes` to `Maybe` (e.g. for
	 * `asort`/`uksort`/etc. which preserve keys but break list ordering).
	 * Other shape information (keys, values, accessories like NonEmpty) is
	 * preserved.
	 */
	public function makeListMaybe(): Type;

	/** @return list<EnumCaseObjectType> */
	public function getEnumCases(): array;

	/**
	 * Returns the single enum case this type represents, or null if not exactly one case.
	 */
	public function getEnumCaseObject(): ?EnumCaseObjectType;

	/**
	 * Returns a list of finite values this type can take.
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

	/** Models the ** operator. */
	public function exponentiate(Type $exponent): Type;

	public function isCallable(): TrinaryLogic;

	/** @return list<CallableParametersAcceptor> */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array;

	public function isCloneable(): TrinaryLogic;

	/** Models the (bool) cast. */
	public function toBoolean(): BooleanType;

	/** Models numeric coercion for arithmetic operators. */
	public function toNumber(): Type;

	/** Models the (int) cast. */
	public function toInteger(): Type;

	/** Models the (float) cast. */
	public function toFloat(): Type;

	/** Models the (string) cast. */
	public function toString(): Type;

	/** Models the (array) cast. */
	public function toArray(): Type;

	/**
	 * Models PHP's implicit array key coercion: floats truncated to int,
	 * booleans become 0/1, null becomes '', numeric strings become int.
	 */
	public function toArrayKey(): Type;

	/**
	 * Returns how this type might change when passed to a typed parameter
	 * or assigned to a typed property.
	 *
	 * With $strictTypes = true: int widens to int|float (since int is accepted
	 * by float parameters in strict mode).
	 * With $strictTypes = false: additional coercions apply, e.g. Stringable
	 * objects are accepted by string parameters.
	 *
	 * Used internally to determine what types a value might be coerced to
	 * when checking parameter acceptance.
	 */
	public function toCoercedArgumentType(bool $strictTypes): self;

	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	public function isSmallerThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	/**
	 * Is Type of a known constant value? Includes literal strings, integers, floats, true, false, null, and array shapes.
	 *
	 * Unlike isConstantScalarValue(), this also returns yes for constant array types (array shapes
	 * with known keys and values). Use this when you need to detect any constant value including arrays.
	 */
	public function isConstantValue(): TrinaryLogic;

	/**
	 * Is Type of a known constant scalar value? Includes literal strings, integers, floats, true, false, and null.
	 *
	 * Unlike isConstantValue(), this does NOT return yes for array shapes.
	 * Use this when you specifically need scalar constants only.
	 */
	public function isConstantScalarValue(): TrinaryLogic;

	/** @return list<ConstantScalarType> */
	public function getConstantScalarTypes(): array;

	/** @return list<int|float|string|bool|null> */
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

	/**
	 * Non-falsy string is a non-empty string that is also not '0'.
	 * Stricter subset of non-empty-string.
	 */
	public function isNonFalsyString(): TrinaryLogic;

	/**
	 * A literal-string is a string composed entirely from string literals
	 * in the source code (not from user input). Used for SQL injection prevention.
	 */
	public function isLiteralString(): TrinaryLogic;

	public function isLowercaseString(): TrinaryLogic;

	public function isUppercaseString(): TrinaryLogic;

	public function isClassString(): TrinaryLogic;

	public function isVoid(): TrinaryLogic;

	public function isScalar(): TrinaryLogic;

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType;

	/**
	 * Type narrowing methods for comparison operators.
	 * For example, for ConstantIntegerType(5), getSmallerType() returns int<min, 4>.
	 */
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
	 * Infers the real types of TemplateTypes found in $this, based on
	 * the received Type. E.g. if $this is array<T> and $receivedType
	 * is array<int>, infers T = int.
	 */
	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap;

	/**
	 * Returns the template types referenced by this Type, recursively.
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

	/** Models abs(). */
	public function toAbsoluteNumber(): Type;

	/**
	 * Returns a new instance with all inner types mapped through $cb.
	 * Returns the same instance if inner types did not change.
	 *
	 * Not used directly — use TypeTraverser::map() instead.
	 *
	 * @param callable(Type):Type $cb
	 */
	public function traverse(callable $cb): Type;

	/**
	 * Like traverse(), but walks two types simultaneously.
	 *
	 * Not used directly — use SimultaneousTypeTraverser::map() instead.
	 *
	 * @param callable(Type $left, Type $right): Type $cb
	 */
	public function traverseSimultaneously(Type $right, callable $cb): Type;

	public function toPhpDocNode(): TypeNode;

	/** @see TypeCombinator::remove() */
	public function tryRemove(Type $typeToRemove): ?Type;

	/**
	 * Removes constant value information. E.g. 'foo' -> string, 1 -> int.
	 * Used when types become too complex to track precisely (e.g. loop iterations).
	 */
	public function generalize(GeneralizePrecision $precision): Type;

	/**
	 * Performance optimization to skip template resolution when no templates are present.
	 */
	public function hasTemplateOrLateResolvableType(): bool;

}
