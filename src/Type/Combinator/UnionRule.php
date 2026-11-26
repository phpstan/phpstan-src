<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;

/**
 * One step of TypeCombinator::union(): it merges the members of one kind, or
 * of every kind, into fewer members holding the same values. union() runs its
 * rules in sequence, each on the members the previous one left; widen() runs
 * more of them, which give up precision.
 *
 * @internal
 */
interface UnionRule
{

	/**
	 * The kinds of the members the rule merges (see TypeKind). union() skips
	 * the rule when no member is of these kinds: a member it merges has
	 * kinds it is of only.
	 */
	public function getKindMask(): int;

	/**
	 * Whether the rule merges members that are each one constant scalar value,
	 * as `1`, `'foo'` or `true`. When it does not, union() also skips it when
	 * every member of its kinds is one.
	 */
	public function mergesConstantScalarValues(): bool;

	/**
	 * @param list<Type> $types the members, none of them a union, an implicit never or an implicit mixed
	 * @param array<int, list<Type>> $normalizedGroups the members of each union passed in, which no rule merges among themselves
	 * @return list<Type>
	 */
	public function apply(array $types, array $normalizedGroups): array;

}
