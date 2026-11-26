<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;

/**
 * One step of TypeCombinator::intersect(): it merges members into fewer
 * members holding the same values. intersect() runs its rules in sequence,
 * each on the members the previous one left; a rule finding that no value is
 * of all of them returns that never alone.
 *
 * @internal
 */
interface IntersectRule
{

	/**
	 * @param list<Type> $types the members, none of them a union or an intersection
	 * @return list<Type>
	 */
	public function apply(array $types): array;

}
