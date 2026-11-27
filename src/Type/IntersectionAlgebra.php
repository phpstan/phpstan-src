<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_splice;
use function count;

/**
 * The union of two intersections as one intersection: the tryUnion() of
 * IntersectionType.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'intersectionAlgebra')]
final class IntersectionAlgebra
{

	/**
	 * `(X & A) | (X & B)` is `X & (A | B)`, so two intersections that differ in
	 * one member only merge exactly when that pair of members does, as
	 * `non-empty-array&hasOffsetValue('a', int)` and
	 * `non-empty-array&hasOffsetValue('a', string)` merge into
	 * `non-empty-array&hasOffsetValue('a', int|string)`.
	 *
	 * @see Type::tryUnion()
	 */
	public static function tryUnion(IntersectionType $type, Type $otherType): ?Type
	{
		if (!$otherType instanceof IntersectionType) {
			return null;
		}

		$types = $type->getTypes();
		$otherTypes = $otherType->getTypes();
		if (count($types) !== count($otherTypes)) {
			return null;
		}

		$different = null;
		foreach ($types as $member) {
			foreach ($otherTypes as $j => $otherMember) {
				if ($member->equals($otherMember)) {
					array_splice($otherTypes, $j, 1);
					continue 2;
				}
			}
			if ($different !== null) {
				return null;
			}
			$different = $member;
		}
		if ($different === null || count($otherTypes) !== 1) {
			return null;
		}

		$merged = $different->tryUnion($otherTypes[0]) ?? $otherTypes[0]->tryUnion($different);
		if ($merged === null) {
			return null;
		}

		$mergedTypes = [$merged];
		foreach ($types as $member) {
			if ($member === $different) {
				continue;
			}
			$mergedTypes[] = $member;
		}

		return TypeCombinator::intersect(...$mergedTypes);
	}

}
