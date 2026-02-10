<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;

/**
 * Marker interface for types that require bidirectional type comparison.
 *
 * Simple types like `StringType` or `IntegerType` can answer `isSuperTypeOf()`
 * and `accepts()` on their own — they check whether the incoming type fits.
 * But compound types (unions, intersections, mixed, never, accessory types,
 * integer ranges, callables, iterables, conditionals, etc.) need to be asked
 * from the other direction, because they carry internal structure that the
 * simple type on the other side knows nothing about.
 *
 * The protocol works like a double dispatch:
 *
 * 1. A simple type's `accepts()`/`isSuperTypeOf()` receives an argument.
 * 2. It checks `if ($type instanceof CompoundType)`.
 * 3. If true, it delegates to `$type->isAcceptedBy($this, …)` or `$type->isSubTypeOf($this)`.
 * 4. The compound type then decomposes itself (e.g., iterates union members)
 *    and calls back to the simple type for each component.
 *
 * This avoids the simple type having to understand union/intersection/mixed/never
 * semantics. For example, `StringType::accepts()` doesn't need to know how to
 * check a `UnionType<string|int>` — it just delegates to `UnionType::isAcceptedBy()`,
 * which iterates its members and asks `StringType::accepts()` for each one.
 *
 * Unlike `instanceof SomeSpecificType` checks (which are discouraged in CLAUDE.md),
 * `instanceof CompoundType` is the correct and intended pattern throughout the
 * type system. It is part of the double-dispatch protocol, not a type query.
 *
 * Implementations include:
 * - `UnionType` — `isSubTypeOf()` requires ALL members to be subtypes, `isAcceptedBy()` requires ALL to be accepted
 * - `IntersectionType` — `isSubTypeOf()` requires at least ONE member to be a subtype (via `maxMin`)
 * - `MixedType`, `NeverType` — terminal cases (mixed accepts everything, never is subtype of everything)
 * - All `AccessoryType` implementations — refinement types that live inside intersections
 * - `IntegerRangeType`, `CallableType`, `IterableType` — types with internal structure
 * - `ConditionalType`, `KeyOfType`, `ValueOfType`, etc. — late-resolvable types
 *
 * @api
 */
interface CompoundType extends Type
{

	/**
	 * Answers "is this compound type accepted by $acceptingType?" from the compound type's perspective.
	 *
	 * Called by simple types when they encounter a CompoundType argument in their `accepts()` method.
	 * The compound type decomposes itself and calls `$acceptingType->accepts()` for each component.
	 *
	 * For example, `UnionType(string|int)::isAcceptedBy(StringType)` asks StringType to accept
	 * `string` and `int` separately, then combines results with `extremeIdentity` (all must pass).
	 */
	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult;

	/**
	 * Answers "is this compound type a subtype of $otherType?" from the compound type's perspective.
	 *
	 * Called by simple types when they encounter a CompoundType argument in their `isSuperTypeOf()` method.
	 * The compound type decomposes itself and calls `$otherType->isSuperTypeOf()` for each component.
	 *
	 * For example, `UnionType(string|int)::isSubTypeOf(MixedType)` asks MixedType whether it is
	 * a supertype of `string` and `int` separately, then combines with `extremeIdentity` (all must pass).
	 */
	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult;

	/**
	 * Compares this compound type against $otherType using greater-than semantics.
	 *
	 * Used for comparison operators (`>`). Each compound type decomposes the comparison
	 * across its members (e.g., IntegerRangeType checks whether all values in the range
	 * are greater than the other type).
	 */
	public function isGreaterThan(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

	/**
	 * Compares this compound type against $otherType using greater-than-or-equal semantics.
	 *
	 * Used for comparison operators (`>=`). Same decomposition strategy as `isGreaterThan()`.
	 */
	public function isGreaterThanOrEqual(Type $otherType, PhpVersion $phpVersion): TrinaryLogic;

}
