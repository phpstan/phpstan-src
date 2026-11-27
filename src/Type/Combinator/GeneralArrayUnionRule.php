<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeKind;
use function array_values;
use function count;

/**
 * Arrays among which is one that is not a shape merge into one general array
 * of all their keys and all their values, as
 * `array<int, string>|array{foo: int}` becomes `array<int|'foo', int|string>`.
 * This loses which keys go with which values.
 *
 * @internal
 */
final class GeneralArrayUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::ARRAY;
	}

	public function mergesConstantScalarValues(): bool
	{
		return true;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		[$arrays, $dropped] = ArrayUnion::getMergeableArrays($types);
		if (count($arrays) < 2) {
			return $types;
		}

		foreach ($arrays as $array) {
			if ($array->getConstantArrays() !== []) {
				continue;
			}

			return ArrayUnion::replaceArrayMembers($types, $arrays + $dropped, [
				ArrayUnion::mergeIntoGeneralArray(array_values($arrays)),
			]);
		}

		return $types;
	}

}
