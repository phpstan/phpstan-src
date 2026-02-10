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
 * @see https://phpstan.org/developing-extensions/type-system
 */
interface Type
{

	/**
	 * Returns all class names referenced anywhere in this type, recursively.
	 *
	 * Includes class names from object types, generic type arguments, callable
	 * signatures, conditional type branches, and any other nested positions.
	 *
	 * Used for dependency tracking and PHPDoc validation to ensure all referenced
	 * classes exist.
	 *
	 * @see Type::getObjectClassNames() for only the direct object type class names
	 *
	 * @return list<non-empty-string>
	 */
	public function getReferencedClasses(): array;

	/**
	 * Returns class names of the object types represented by this type.
	 *
	 * Unlike getReferencedClasses(), this only returns class names of actual
	 * object types — not classes referenced in generic arguments, callable
	 * signatures, or other nested positions.
	 *
	 * For a union type like Foo|Bar, returns ['Foo', 'Bar'].
	 * For a non-object type like string, returns [].
	 *
	 * @return list<non-empty-string>
	 */
	public function getObjectClassNames(): array;

	/**
	 * Returns ClassReflection instances for the object types represented by this type.
	 *
	 * Like getObjectClassNames() but returns full ClassReflection objects,
	 * giving access to methods, properties, constants, parent classes, interfaces,
	 * generics, PHPDocs, and attributes.
	 *
	 * @return list<ClassReflection>
	 */
	public function getObjectClassReflections(): array;

	/**
	 * Returns the object type for a class-string type or a literal class name string.
	 *
	 * For class-string<Foo>, returns the object type Foo.
	 * For a literal string 'Foo' where Foo is a valid class, returns the object type Foo.
	 * For non-class-string types, returns ErrorType.
	 *
	 * Used in contexts like `new $className()` where a class-string is instantiated.
	 */
	public function getClassStringObjectType(): Type;

	/**
	 * Returns the object type for class-string types, literal class name strings,
	 * and object types themselves.
	 *
	 * Like getClassStringObjectType(), but also returns object types as-is.
	 * For class-string<Foo>, returns the object type Foo.
	 * For a literal string 'Foo' (if valid class), returns the object type Foo.
	 * For an object type Foo, returns the object type Foo.
	 *
	 * Used in contexts like static method/property access where the left side
	 * can be either a class-string or an object: `$classOrObject::method()`.
	 */
	public function getObjectTypeOrClassStringObjectType(): Type;

	/**
	 * Returns whether this type is an object type.
	 *
	 * For ObjectType and its subtypes, returns yes.
	 * For union types, returns yes if all members are objects,
	 * maybe if some are, no if none are.
	 * For non-object types (scalars, arrays, etc.), returns no.
	 */
	public function isObject(): TrinaryLogic;

	/**
	 * Returns whether this type is an enum type.
	 *
	 * Returns yes for enum types and enum case types.
	 * Returns maybe for generic object types that might be enums.
	 * Returns no for non-enum types.
	 */
	public function isEnum(): TrinaryLogic;

	/**
	 * Returns the array type instances contained in this type.
	 *
	 * For a union type like array<string>|array<int>, returns both array types.
	 * For non-array types, returns an empty array.
	 *
	 * Used when you need to iterate over each array component separately,
	 * such as when computing array operation results.
	 *
	 * @see Type::getConstantArrays() for only array shapes with known structure
	 *
	 * @return list<ArrayType|ConstantArrayType>
	 */
	public function getArrays(): array;

	/**
	 * Returns the constant array type instances (array shapes) contained in this type.
	 *
	 * Unlike getArrays(), only returns ConstantArrayType instances — arrays with
	 * known keys and value types like array{name: string, age: int}.
	 * Generic array types like array<string, int> are excluded.
	 *
	 * @return list<ConstantArrayType>
	 */
	public function getConstantArrays(): array;

	/**
	 * Returns the constant string type instances contained in this type.
	 *
	 * For a union type like 'foo'|'bar', returns both constant string types.
	 * For a generic string type, returns an empty array.
	 *
	 * Used when you need the actual literal string values, for example
	 * when evaluating string functions on known values.
	 *
	 * @return list<ConstantStringType>
	 */
	public function getConstantStrings(): array;

	/**
	 * Checks whether this type accepts the given type for assignment or parameter passing.
	 *
	 * This is the method used by rules to validate that a value can be passed to a
	 * parameter, assigned to a typed property, or returned from a typed function.
	 *
	 * Unlike isSuperTypeOf(), accepts() takes into account PHP's implicit type coercion.
	 * With $strictTypes = false, int is accepted by float, and Stringable objects are
	 * accepted by string. With $strictTypes = true, the behavior is closer to isSuperTypeOf().
	 *
	 * Returns AcceptsResult with TrinaryLogic (yes/maybe/no) and optional reasons
	 * explaining why the type was not accepted, for better error messages.
	 */
	public function accepts(Type $type, bool $strictTypes): AcceptsResult;

	/**
	 * Checks whether this type is a supertype of the given type.
	 *
	 * This is the fundamental type relationship method. It answers the question:
	 * "Does every value of $type belong to $this type?"
	 *
	 * Examples:
	 *   (new StringType())->isSuperTypeOf(new ConstantStringType('foo'))  // yes
	 *   (new StringType())->isSuperTypeOf(new IntegerType())             // no
	 *   (new StringType())->isSuperTypeOf(new MixedType())               // maybe
	 *
	 * Returns IsSuperTypeOfResult with TrinaryLogic (yes/maybe/no) and optional
	 * reasons. Use ->yes(), ->maybe(), ->no() to check the result.
	 *
	 * This method is preferable to instanceof checks because it correctly handles
	 * union types, intersection types, and all other composite types.
	 */
	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult;

	/**
	 * Checks whether two types are structurally equal.
	 *
	 * Returns true only if the types are exactly the same — not merely compatible.
	 * Unlike isSuperTypeOf(), this is a strict binary check (true/false).
	 *
	 * For example, int|float is NOT equal to int (but int|float is a supertype of int).
	 *
	 * Used for cache validation, result deduplication, and checking if a type changed
	 * across analysis iterations.
	 */
	public function equals(Type $type): bool;

	/**
	 * Returns a human-readable string representation of this type.
	 *
	 * The verbosity level controls how much detail is included:
	 * - VerbosityLevel::typeOnly(): Simple type name (e.g. "string", "Foo")
	 * - VerbosityLevel::value(): Includes constant values (e.g. "'hello'", "1|2|3")
	 * - VerbosityLevel::precise(): Full details including accessory types (e.g. "non-empty-list<string>")
	 * - VerbosityLevel::cache(): Most detailed, for internal caching purposes
	 *
	 * Used in error messages, debugging output, and test assertions.
	 * Use VerbosityLevel::getRecommendedLevelByType() to choose the appropriate level
	 * for error messages based on the types being compared.
	 */
	public function describe(VerbosityLevel $level): string;

	/**
	 * Returns whether property access ($obj->prop) is possible on this type.
	 *
	 * Returns yes for object types that support property access.
	 * Returns maybe for mixed types.
	 * Returns no for scalars, arrays, and other non-object types.
	 *
	 * This is a general capability check. Use hasInstanceProperty() or hasStaticProperty()
	 * to check for a specific property.
	 */
	public function canAccessProperties(): TrinaryLogic;

	/** @deprecated Use hasInstanceProperty or hasStaticProperty instead */
	public function hasProperty(string $propertyName): TrinaryLogic;

	/** @deprecated Use getInstanceProperty or getStaticProperty instead */
	public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	/** @deprecated Use getUnresolvedInstancePropertyPrototype or getUnresolvedStaticPropertyPrototype instead */
	public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	/**
	 * Returns whether a specific instance property exists on this type.
	 *
	 * Queries reflection to find the property definition. Returns yes if the
	 * property definitely exists, no if it definitely doesn't, or maybe if
	 * it might exist (e.g. on a mixed type).
	 */
	public function hasInstanceProperty(string $propertyName): TrinaryLogic;

	/**
	 * Returns the reflection for a specific instance property.
	 *
	 * The ClassMemberAccessAnswerer provides scope context for visibility checks.
	 * Call hasInstanceProperty() first to verify the property exists.
	 */
	public function getInstanceProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	/**
	 * Returns the unresolved property prototype for a specific instance property.
	 *
	 * Unlike getInstanceProperty(), this returns an intermediate representation
	 * that allows deferring template type resolution and applying transformations
	 * based on the called-on type (e.g. resolving static/self to the actual type).
	 *
	 * Use getInstanceProperty() in most rule implementations.
	 * Use this method in framework code that needs to apply type transformations.
	 */
	public function getUnresolvedInstancePropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	/**
	 * Returns whether a specific static property exists on this type.
	 */
	public function hasStaticProperty(string $propertyName): TrinaryLogic;

	/**
	 * Returns the reflection for a specific static property.
	 *
	 * The ClassMemberAccessAnswerer provides scope context for visibility checks.
	 * Call hasStaticProperty() first to verify the property exists.
	 */
	public function getStaticProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection;

	/**
	 * Returns the unresolved property prototype for a specific static property.
	 *
	 * @see Type::getUnresolvedInstancePropertyPrototype() for explanation of resolved vs unresolved
	 */
	public function getUnresolvedStaticPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection;

	/**
	 * Returns whether method calls ($obj->method()) are possible on this type.
	 *
	 * Returns yes for object types.
	 * Returns maybe for mixed types.
	 * Returns no for scalars, arrays, and other types that don't support method calls.
	 *
	 * This is a general capability check. Use hasMethod() to check for a specific method.
	 */
	public function canCallMethods(): TrinaryLogic;

	/**
	 * Returns whether a specific method exists on this type.
	 *
	 * Queries reflection for the method definition. Returns yes if the method
	 * definitely exists, no if it definitely doesn't, or maybe if it might
	 * exist (e.g. on a mixed type, or via __call).
	 */
	public function hasMethod(string $methodName): TrinaryLogic;

	/**
	 * Returns the reflection for a specific method.
	 *
	 * The ClassMemberAccessAnswerer provides scope context for visibility checks.
	 * Call hasMethod() first to verify the method exists.
	 */
	public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection;

	/**
	 * Returns the unresolved method prototype for a specific method.
	 *
	 * Unlike getMethod(), this returns an intermediate representation that allows
	 * deferring template type resolution and applying transformations based on
	 * the called-on type (e.g. resolving static return types).
	 *
	 * Use getMethod() in most rule implementations.
	 * Use this method in framework code that needs to apply type transformations.
	 */
	public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection;

	/**
	 * Returns whether class constant access (Foo::CONST) is possible on this type.
	 *
	 * Returns yes for object/class types.
	 * Returns maybe for mixed types.
	 * Returns no for scalars, arrays, and other types that don't support constant access.
	 *
	 * This is a general capability check. Use hasConstant() to check for a specific constant.
	 */
	public function canAccessConstants(): TrinaryLogic;

	/**
	 * Returns whether a specific class constant exists on this type.
	 */
	public function hasConstant(string $constantName): TrinaryLogic;

	/**
	 * Returns the reflection for a specific class constant.
	 *
	 * Call hasConstant() first to verify the constant exists.
	 */
	public function getConstant(string $constantName): ClassConstantReflection;

	/**
	 * Returns whether this type can be iterated over (in a foreach loop).
	 *
	 * Iterable types include arrays, objects implementing Traversable
	 * (Iterator or IteratorAggregate), and the iterable pseudo-type.
	 *
	 * Returns yes for arrays and Traversable objects.
	 * Returns maybe for mixed types.
	 * Returns no for scalars and non-traversable objects.
	 */
	public function isIterable(): TrinaryLogic;

	/**
	 * Returns whether this type is iterable and guaranteed to be non-empty.
	 *
	 * Unlike isIterable(), this also asserts the iterable has at least one element.
	 *
	 * Returns yes for non-empty arrays and non-empty iterables.
	 * Returns no for empty arrays or types that might be empty.
	 */
	public function isIterableAtLeastOnce(): TrinaryLogic;

	/**
	 * Returns the count of elements as a Type, typically an IntegerRangeType.
	 *
	 * For constant arrays, returns the precise count (possibly a range
	 * accounting for optional keys).
	 * For generic arrays, returns int<0, max>.
	 * For non-empty arrays, returns int<1, max>.
	 *
	 * Used by count() return type extensions and array size comparisons.
	 */
	public function getArraySize(): Type;

	/**
	 * Returns the key type of this iterable.
	 *
	 * Works for both arrays and Traversable objects.
	 * For array<string, int>, returns string.
	 * For Iterator<int, Foo>, returns int.
	 * For non-iterable types, returns ErrorType.
	 */
	public function getIterableKeyType(): Type;

	/** @deprecated use getIterableKeyType */
	public function getFirstIterableKeyType(): Type;

	/** @deprecated use getIterableKeyType */
	public function getLastIterableKeyType(): Type;

	/**
	 * Returns the value type of this iterable.
	 *
	 * Works for both arrays and Traversable objects.
	 * For array<string, int>, returns int.
	 * For Iterator<int, Foo>, returns Foo.
	 * For non-iterable types, returns ErrorType.
	 */
	public function getIterableValueType(): Type;

	/** @deprecated use getIterableValueType */
	public function getFirstIterableValueType(): Type;

	/** @deprecated use getIterableValueType */
	public function getLastIterableValueType(): Type;

	/**
	 * Returns whether this type is an array.
	 *
	 * Returns yes for all array types (generic arrays, constant arrays, lists).
	 * Returns no for non-array types including objects implementing ArrayAccess.
	 */
	public function isArray(): TrinaryLogic;

	/**
	 * Returns whether this type is a constant array (array shape).
	 *
	 * Constant arrays have known keys and value types, like array{name: string, age: int}.
	 * Generic arrays like array<string, int> are NOT constant arrays.
	 *
	 * Returns yes for ConstantArrayType instances.
	 * Returns no for generic array types and non-array types.
	 */
	public function isConstantArray(): TrinaryLogic;

	/**
	 * Returns whether this type is an oversized array.
	 *
	 * An oversized array is a constant array shape that grew too large to track
	 * precisely and was degraded to a generic array type with an OversizedArrayType
	 * accessory. This prevents performance issues from tracking thousands of keys.
	 *
	 * Returns yes for OversizedArrayType.
	 * Returns maybe for generic arrays (which could be oversized).
	 * Returns no for constant arrays (known size, not oversized).
	 */
	public function isOversizedArray(): TrinaryLogic;

	/**
	 * Returns whether this type is a list (sequential integer-keyed array).
	 *
	 * A list is an array with sequential integer keys starting from 0 with no gaps.
	 * For example, array{0: 'a', 1: 'b', 2: 'c'} is a list.
	 * array{0: 'a', 2: 'c'} is NOT a list (gap at key 1).
	 * array{name: string} is NOT a list (string key).
	 *
	 * Returns yes for types known to be lists.
	 * Returns maybe for generic arrays that might be lists.
	 * Returns no for arrays known not to be lists and non-array types.
	 */
	public function isList(): TrinaryLogic;

	/**
	 * Returns whether this type supports array offset access ($a[$key]).
	 *
	 * Returns yes for arrays, strings, and objects implementing ArrayAccess.
	 * Returns maybe for mixed types.
	 * Returns no for scalars (other than string) and non-ArrayAccess objects.
	 */
	public function isOffsetAccessible(): TrinaryLogic;

	/**
	 * Returns whether accessing an undefined offset on this type is legal.
	 *
	 * Unlike isOffsetAccessible() which checks if offset access is supported at all,
	 * this checks whether accessing a non-existent offset is safe (won't cause errors).
	 *
	 * Returns yes for arrays and strings (return null/empty string for missing offsets).
	 * Used by rules to decide whether to report undefined offset access errors.
	 */
	public function isOffsetAccessLegal(): TrinaryLogic;

	/**
	 * Returns whether the given offset exists in this type.
	 *
	 * For constant arrays, checks whether the specific key exists.
	 * For generic arrays, returns maybe (the key could exist).
	 * For objects implementing ArrayAccess, checks parameter compatibility.
	 *
	 * Returns yes if the offset definitely exists, no if it definitely doesn't,
	 * or maybe if it might exist.
	 */
	public function hasOffsetValueType(Type $offsetType): TrinaryLogic;

	/**
	 * Returns the type of the value at the given offset.
	 *
	 * For constant arrays, returns the value type for the specific key.
	 * For generic arrays, returns the generic value type.
	 * For strings, returns a single-character string type.
	 *
	 * Check hasOffsetValueType() first to determine whether the offset exists.
	 */
	public function getOffsetValueType(Type $offsetType): Type;

	/**
	 * Returns a new type with the offset set to the given value type.
	 *
	 * This may add a new key to the array. It can change the array structure
	 * (e.g. break list type if the key is not sequential).
	 *
	 * When $offsetType is null, the value is appended (like $a[] = $value).
	 * When $unionValues is true, the new value type is unioned with any existing
	 * value type at that offset.
	 *
	 * @see Type::setExistingOffsetValueType() for modifying an existing key without widening
	 */
	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type;

	/**
	 * Returns a new type with an existing offset's value type changed.
	 *
	 * Unlike setOffsetValueType(), this assumes the key already exists.
	 * It preserves the array shape and list type — it does not add new keys
	 * or widen the array.
	 *
	 * Used when modifying a known array element, like $a[$existingKey] = $newValue.
	 */
	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type;

	/**
	 * Returns a new type with the given offset removed.
	 *
	 * For constant arrays, removes the specific key.
	 * For generic arrays, returns the same type (cannot determine which key was removed).
	 *
	 * Models the behavior of unset($a[$key]).
	 */
	public function unsetOffset(Type $offsetType): Type;

	/**
	 * Returns the keys of this array as a list type, filtered to only include
	 * keys whose values match the given filter type.
	 *
	 * Models the behavior of array_keys($array, $searchValue, $strict).
	 * The $strict parameter controls whether loose (==) or strict (===) comparison
	 * is used to match values.
	 */
	public function getKeysArrayFiltered(Type $filterValueType, TrinaryLogic $strict): Type;

	/**
	 * Returns the keys of this array as a list type.
	 *
	 * Models the behavior of array_keys($array).
	 * Returns a list<keyType> — always a list with integer keys starting from 0.
	 */
	public function getKeysArray(): Type;

	/**
	 * Returns the values of this array as a reindexed list type.
	 *
	 * Models the behavior of array_values($array).
	 * Returns a list<valueType> — always a list with integer keys starting from 0.
	 */
	public function getValuesArray(): Type;

	/**
	 * Returns the type resulting from splitting this array into chunks.
	 *
	 * Models the behavior of array_chunk($array, $length, $preserveKeys).
	 * Returns a list of arrays, each containing up to $lengthType elements.
	 * When $preserveKeys is yes, original keys are preserved within each chunk.
	 * When $preserveKeys is no, each chunk is reindexed as a list.
	 */
	public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type;

	/**
	 * Returns the type resulting from using this array's values as keys
	 * filled with the given value type.
	 *
	 * Models the behavior of array_fill_keys($keys, $value) where $keys
	 * is this array's values.
	 */
	public function fillKeysArray(Type $valueType): Type;

	/**
	 * Returns the type resulting from swapping keys and values.
	 *
	 * Models the behavior of array_flip($array).
	 * Original keys become values and original values become keys
	 * (values are converted to valid array keys via toArrayKey()).
	 */
	public function flipArray(): Type;

	/**
	 * Returns the type resulting from keeping only keys that exist in the other arrays.
	 *
	 * Models the behavior of array_intersect_key($array, ...$otherArrays).
	 * Retains entries from this array whose keys also exist in $otherArraysType.
	 */
	public function intersectKeyArray(Type $otherArraysType): Type;

	/**
	 * Returns the type resulting from removing the last element.
	 *
	 * Models the effect of array_pop() on the array.
	 * For constant arrays, removes the last entry.
	 * For generic arrays, returns the same type.
	 */
	public function popArray(): Type;

	/**
	 * Returns the type resulting from reversing element order.
	 *
	 * Models the behavior of array_reverse($array, $preserveKeys).
	 * When $preserveKeys is yes, original keys are preserved.
	 * When $preserveKeys is no, integer keys are reindexed.
	 */
	public function reverseArray(TrinaryLogic $preserveKeys): Type;

	/**
	 * Returns the type of the key found when searching for a value.
	 *
	 * Models the behavior of array_search($needle, $array, $strict).
	 * Returns a union of matching key types, or false if no match is found.
	 * When $strict is yes, uses strict comparison (===).
	 * When $strict is no or null, uses loose comparison (==).
	 */
	public function searchArray(Type $needleType, ?TrinaryLogic $strict = null): Type;

	/**
	 * Returns the type resulting from removing the first element.
	 *
	 * Models the effect of array_shift() on the array.
	 * For constant arrays, removes the first entry and reindexes integer keys.
	 * For generic arrays, returns the same type.
	 */
	public function shiftArray(): Type;

	/**
	 * Returns the type resulting from randomizing element order.
	 *
	 * Models the effect of shuffle() on the array.
	 * The result is always a list (integer keys starting from 0).
	 * Constant array type information is degraded since order is unknown.
	 */
	public function shuffleArray(): Type;

	/**
	 * Returns the type resulting from extracting a portion of the array.
	 *
	 * Models the behavior of array_slice($array, $offset, $length, $preserveKeys).
	 * Extracts elements starting at $offsetType with optional $lengthType limit.
	 * When $preserveKeys is yes, original keys are kept.
	 * When $preserveKeys is no, integer keys are reindexed.
	 */
	public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type;

	/**
	 * Returns the type resulting from removing and replacing a portion of the array.
	 *
	 * Models the effect of array_splice() on the array (the modified array, not the removed portion).
	 * Removes elements starting at $offsetType for $lengthType entries, then inserts
	 * $replacementType elements in their place.
	 */
	public function spliceArray(Type $offsetType, Type $lengthType, Type $replacementType): Type;

	/**
	 * Returns all enum cases represented by this type.
	 *
	 * For a specific enum case type, returns that single case.
	 * For a full enum type, returns all defined cases.
	 * For a union of enum cases, returns all cases in the union.
	 * For non-enum types, returns an empty array.
	 *
	 * @return list<EnumCaseObjectType>
	 */
	public function getEnumCases(): array;

	/**
	 * Returns the single enum case this type represents, or null.
	 *
	 * Unlike getEnumCases() which returns all cases, this returns a single
	 * EnumCaseObjectType only when the type represents exactly one enum case.
	 * Returns null for non-enum types, full enum types, or unions of multiple cases.
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
	 * Used to determine if a check covers all possible values
	 * (e.g. in switch exhaustiveness analysis).
	 *
	 * @return list<Type>
	 */
	public function getFiniteTypes(): array;

	/**
	 * Returns the type resulting from raising this type to the power of $exponent.
	 *
	 * Models the ** operator. For integer and float types, returns the appropriate
	 * numeric result type. For non-numeric types, returns ErrorType.
	 */
	public function exponentiate(Type $exponent): Type;

	/**
	 * Returns whether this type can be called as a function/method.
	 *
	 * Returns yes for Closure, callable types, callable strings, callable arrays,
	 * and objects with __invoke().
	 * Returns maybe for mixed types and generic strings.
	 * Returns no for non-callable types.
	 */
	public function isCallable(): TrinaryLogic;

	/**
	 * Returns the parameter acceptors (signatures) for this callable type.
	 *
	 * Each CallableParametersAcceptor describes one possible signature of the callable,
	 * including parameters and return type. Multiple entries indicate overloaded signatures.
	 *
	 * Call isCallable() first to verify this type is callable.
	 *
	 * @return list<CallableParametersAcceptor>
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array;

	/**
	 * Returns whether values of this type can be cloned.
	 *
	 * Returns yes for object types (unless clone is restricted).
	 * Returns no for non-object types.
	 */
	public function isCloneable(): TrinaryLogic;

	/**
	 * Returns the type resulting from casting to bool.
	 *
	 * Models the (bool) cast and boolean coercion in conditions.
	 * Empty arrays, 0, 0.0, '', '0', null, and false are falsy.
	 * Everything else is truthy.
	 */
	public function toBoolean(): BooleanType;

	/**
	 * Returns the type resulting from numeric coercion (int or float).
	 *
	 * Models the implicit conversion that occurs with arithmetic operators.
	 * Numeric strings become int or float, booleans become 0 or 1.
	 * Arrays and non-numeric types return ErrorType.
	 */
	public function toNumber(): Type;

	/**
	 * Returns the type resulting from casting to int.
	 *
	 * Models the (int) cast. Floats are truncated, strings are parsed,
	 * booleans become 0 or 1, null becomes 0.
	 * Arrays return ErrorType.
	 */
	public function toInteger(): Type;

	/**
	 * Returns the type resulting from casting to float.
	 *
	 * Models the (float) cast. Integers become floats, strings are parsed,
	 * booleans become 0.0 or 1.0, null becomes 0.0.
	 * Arrays return ErrorType.
	 */
	public function toFloat(): Type;

	/**
	 * Returns the type resulting from casting to string.
	 *
	 * Models the (string) cast. Integers, floats, and booleans become their
	 * string representations. Objects with __toString() return their string type.
	 * Arrays and objects without __toString() return ErrorType.
	 */
	public function toString(): Type;

	/**
	 * Returns the type resulting from casting to array.
	 *
	 * Models the (array) cast. Arrays return themselves, scalars are wrapped
	 * in a single-element array, and objects are converted to their property arrays.
	 */
	public function toArray(): Type;

	/**
	 * Returns the type when used as an array key.
	 *
	 * Models PHP's implicit array key coercion: floats are truncated to int,
	 * booleans become 0/1, null becomes '', and strings that look like integers
	 * are converted to int. Objects and arrays return ErrorType since they
	 * cannot be used as array keys.
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

	/**
	 * Returns whether this type is definitely smaller than the given type
	 * using PHP's < operator semantics.
	 *
	 * Takes PhpVersion into account because comparison behavior varies across
	 * PHP versions (e.g. comparing objects to other types).
	 */
	public function isSmallerThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	/**
	 * Returns whether this type is definitely smaller than or equal to the given type
	 * using PHP's <= operator semantics.
	 */
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

	/**
	 * Returns the constant scalar type instances contained in this type.
	 *
	 * For a union like 1|2|'foo', returns [ConstantIntegerType(1), ConstantIntegerType(2), ConstantStringType('foo')].
	 * For non-constant or infinite types, returns an empty array.
	 *
	 * @return list<ConstantScalarType>
	 */
	public function getConstantScalarTypes(): array;

	/**
	 * Returns the actual PHP values of constant scalar types.
	 *
	 * For a union like 1|2|'foo', returns [1, 2, 'foo'].
	 * For non-constant or infinite types, returns an empty array.
	 *
	 * @return list<int|float|string|bool|null>
	 */
	public function getConstantScalarValues(): array;

	/**
	 * Returns whether this type is the null type.
	 */
	public function isNull(): TrinaryLogic;

	/**
	 * Returns whether this type is the true type.
	 */
	public function isTrue(): TrinaryLogic;

	/**
	 * Returns whether this type is the false type.
	 */
	public function isFalse(): TrinaryLogic;

	/**
	 * Returns whether this type is a boolean type (true, false, or bool).
	 */
	public function isBoolean(): TrinaryLogic;

	/**
	 * Returns whether this type is a float type.
	 */
	public function isFloat(): TrinaryLogic;

	/**
	 * Returns whether this type is an integer type.
	 */
	public function isInteger(): TrinaryLogic;

	/**
	 * Returns whether this type is a string type.
	 *
	 * Returns yes for all string types including constant strings, numeric strings,
	 * class-string, non-empty-string, literal-string, etc.
	 */
	public function isString(): TrinaryLogic;

	/**
	 * Returns whether this type is a numeric string type.
	 *
	 * A numeric string is a string that PHP considers valid for arithmetic,
	 * like '123', '1.5', or '0x1A'. Returns yes for AccessoryNumericStringType
	 * and constant strings that are numeric.
	 */
	public function isNumericString(): TrinaryLogic;

	/**
	 * Returns whether this type is a non-empty string type.
	 *
	 * Returns yes for strings guaranteed to have length >= 1,
	 * including non-falsy strings, class-strings, and non-empty constant strings.
	 * Returns no for '' (empty string constant) and generic string types.
	 */
	public function isNonEmptyString(): TrinaryLogic;

	/**
	 * Returns whether this type is a non-falsy string type.
	 *
	 * A non-falsy string is a non-empty string that is also not '0'.
	 * This is a stricter subset of non-empty-string.
	 * Returns yes for AccessoryNonFalsyStringType and qualifying constant strings.
	 */
	public function isNonFalsyString(): TrinaryLogic;

	/**
	 * Returns whether this type is a literal string type.
	 *
	 * A literal-string is a string whose value was composed entirely from
	 * string literals in the source code (not from user input). Used for
	 * SQL injection prevention — literal strings are safe for query building.
	 */
	public function isLiteralString(): TrinaryLogic;

	/**
	 * Returns whether this type is a lowercase string type.
	 *
	 * Returns yes for strings known to be entirely lowercase, such as the result
	 * of strtolower() or constant strings where strtolower($value) === $value.
	 */
	public function isLowercaseString(): TrinaryLogic;

	/**
	 * Returns whether this type is an uppercase string type.
	 *
	 * Returns yes for strings known to be entirely uppercase, such as the result
	 * of strtoupper() or constant strings where strtoupper($value) === $value.
	 */
	public function isUppercaseString(): TrinaryLogic;

	/**
	 * Returns whether this type is a class-string type.
	 *
	 * A class-string is a string that contains a valid fully-qualified class name.
	 * Returns yes for class-string, class-string<Foo>, and literal strings that
	 * are known class names. Returns maybe for generic strings.
	 */
	public function isClassString(): TrinaryLogic;

	/**
	 * Returns whether this type is the void type.
	 *
	 * Void is a return-type-only type that indicates a function returns no value.
	 * It cannot be used in union types or as a parameter type.
	 */
	public function isVoid(): TrinaryLogic;

	/**
	 * Returns whether this type is a scalar type.
	 *
	 * Scalar types are int, float, string, and bool.
	 * Returns yes for all scalar types including their constant subtypes.
	 * Returns no for arrays, objects, null, void, and resource.
	 */
	public function isScalar(): TrinaryLogic;

	/**
	 * Returns the result of a loose comparison (==) between this type and the given type.
	 *
	 * Models PHP's type juggling comparison rules. Returns a BooleanType that
	 * may be true, false, or bool (when the result is uncertain).
	 * Takes PhpVersion into account because loose comparison behavior varies
	 * across PHP versions (e.g. 0 == "foo" changed in PHP 8.0).
	 */
	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType;

	/**
	 * Returns a type representing all values that are smaller than this type.
	 *
	 * Used for type narrowing after < comparisons.
	 * For example, for ConstantIntegerType(5), returns int<min, 4>.
	 */
	public function getSmallerType(PhpVersion $phpVersion): Type;

	/**
	 * Returns a type representing all values that are smaller than or equal to this type.
	 *
	 * Used for type narrowing after <= comparisons.
	 * For example, for ConstantIntegerType(5), returns int<min, 5>.
	 */
	public function getSmallerOrEqualType(PhpVersion $phpVersion): Type;

	/**
	 * Returns a type representing all values that are greater than this type.
	 *
	 * Used for type narrowing after > comparisons.
	 * For example, for ConstantIntegerType(5), returns int<6, max>.
	 */
	public function getGreaterType(PhpVersion $phpVersion): Type;

	/**
	 * Returns a type representing all values that are greater than or equal to this type.
	 *
	 * Used for type narrowing after >= comparisons.
	 * For example, for ConstantIntegerType(5), returns int<5, max>.
	 */
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
	 * Infers template types.
	 *
	 * Infers the real Type of the TemplateTypes found in $this, based on
	 * the received Type. For example, if $this is array<T> and $receivedType
	 * is array<int>, it infers T = int.
	 *
	 * Returns a TemplateTypeMap mapping template type names to their inferred types.
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

	/**
	 * Returns the type resulting from taking the absolute value.
	 *
	 * Models the abs() function. For negative constant integers/floats, returns the
	 * positive counterpart. For integer ranges, adjusts bounds accordingly.
	 * For non-numeric types, returns ErrorType.
	 */
	public function toAbsoluteNumber(): Type;

	/**
	 * Traverses inner types.
	 *
	 * Returns a new instance with all inner types mapped through $cb. Might
	 * return the same instance if inner types did not change.
	 *
	 * Used to resolve template types, transform nested types, or collect
	 * information about type structure. For example, replacing TemplateType
	 * placeholders with concrete types in a generic instantiation.
	 *
	 * @param callable(Type):Type $cb
	 */
	public function traverse(callable $cb): Type;

	/**
	 * Traverses inner types while keeping the same structure in another type.
	 *
	 * Like traverse(), but walks two types simultaneously, passing matching
	 * pairs of inner types from $this and $right to the callback. Used when
	 * two types with the same structure need to be compared or merged element-wise.
	 *
	 * @param callable(Type $left, Type $right): Type $cb
	 */
	public function traverseSimultaneously(Type $right, callable $cb): Type;

	/**
	 * Converts this Type to its PHPDoc AST node representation.
	 *
	 * Used to serialize types back to PHPDoc format, for example when generating
	 * PHPDoc annotations or converting types for display in documentation tools.
	 */
	public function toPhpDocNode(): TypeNode;

	/**
	 * Return the difference with another type, or null if it cannot be represented.
	 *
	 * For example, int|string minus string returns int.
	 * Returns null when the subtraction cannot be cleanly represented as a Type.
	 *
	 * @see TypeCombinator::remove()
	 */
	public function tryRemove(Type $typeToRemove): ?Type;

	/**
	 * Generalizes this type by removing constant value information.
	 *
	 * Converts specific/literal types to their more general equivalents:
	 * - GeneralizePrecision::lessSpecific(): Full generalization, e.g. 'foo' -> string, 1 -> int
	 * - GeneralizePrecision::moreSpecific(): Partial generalization, preserves some detail (used in loop analysis)
	 * - GeneralizePrecision::templateArgument(): For template argument generalization
	 *
	 * Used when types become too complex to track precisely, such as after
	 * repeated loop iterations where constant arrays grow unboundedly.
	 */
	public function generalize(GeneralizePrecision $precision): Type;

	/**
	 * Returns whether this type contains any template types or late-resolvable types.
	 *
	 * Template types are generic type parameters (T, TValue, etc.) waiting to be resolved.
	 * Late-resolvable types are types that cannot be fully determined during initial analysis.
	 *
	 * Used as a performance optimization to skip template resolution logic when
	 * no templates are present.
	 */
	public function hasTemplateOrLateResolvableType(): bool;

}
