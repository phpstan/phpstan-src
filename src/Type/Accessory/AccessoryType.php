<?php declare(strict_types = 1);

namespace PHPStan\Type\Accessory;

use PHPStan\Type\Type;

/**
 * Marker interface for types that refine a base type with additional constraints.
 *
 * An AccessoryType never stands alone — it always exists inside an `IntersectionType`
 * alongside a base type (like `StringType` or `ArrayType`). The base type provides
 * the fundamental type identity, and the AccessoryType adds a narrowing guarantee.
 *
 * For example, `non-empty-string` is represented as:
 *
 *     IntersectionType([StringType, AccessoryNonEmptyStringType])
 *
 * And `non-empty-list<int>` is represented as:
 *
 *     IntersectionType([ArrayType(int, int), NonEmptyArrayType, AccessoryArrayListType])
 *
 * Each AccessoryType implementation has a corresponding query method on the `Type` interface
 * that returns `TrinaryLogic`. This lets any code query the refinement without knowing
 * how it is represented internally:
 *
 * | AccessoryType class              | Corresponding Type method       |
 * |----------------------------------|---------------------------------|
 * | AccessoryNonEmptyStringType      | `isNonEmptyString()`            |
 * | AccessoryNonFalsyStringType      | `isNonFalsyString()`            |
 * | AccessoryLiteralStringType       | `isLiteralString()`             |
 * | AccessoryNumericStringType       | `isNumericString()`             |
 * | AccessoryLowercaseStringType     | `isLowercaseString()`           |
 * | AccessoryUppercaseStringType     | `isUppercaseString()`           |
 * | AccessoryArrayListType           | `isList()`                      |
 * | NonEmptyArrayType                | `isIterableAtLeastOnce()`       |
 * | OversizedArrayType               | `isOversizedArray()`            |
 * | HasMethodType                    | `hasMethod(string)`             |
 * | HasPropertyType                  | `hasProperty(string)`           |
 * | HasOffsetType                    | `hasOffsetValueType(Type)`      |
 * | HasOffsetValueType               | `getOffsetValueType(Type)`      |
 *
 * All implementations also implement `CompoundType`, so they participate in the
 * double-dispatch protocol for type comparison — simple types delegate to
 * `$type->isAcceptedBy()`/`$type->isSubTypeOf()` when they encounter an AccessoryType.
 *
 * The `instanceof AccessoryType` check is used in a few specific places:
 * - `IntersectionType::describe()` — skips AccessoryTypes when building base type names
 *   (they are rendered as type-level qualifiers like `non-empty-` instead)
 * - `IntersectionType::describeItself()` — separates base types from accessory types
 *   when composing the human-readable type description
 * - `UnionTypeHelper::sortTypes()` — sorts AccessoryTypes after base types
 * - `TypeCombinator` — handles AccessoryType intersection/union normalization
 * - `MissingTypehintCheck` — skips AccessoryTypes in typehint analysis
 */
interface AccessoryType extends Type
{

	/**
	 * Returns the base type this accessory refines.
	 *
	 * Used when an accessory would otherwise end up in an `IntersectionType`
	 * without an explicit base type — the returned type provides that base.
	 * For example `AccessoryNonEmptyStringType` returns `string`, array accessories
	 * return `array`, and offset accessories return `array|ArrayAccess`.
	 */
	public function getDefaultBaseType(): Type;

}
