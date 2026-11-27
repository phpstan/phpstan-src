<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeKind;
use function array_values;
use function count;

/**
 * Array shapes merge exactly where ConstantArrayType::tryUnion() merges them,
 * as `array{a: 1}|array{a: 2}` becomes `array{a: 1|2}`. The arrays keep the
 * accessory types they all have. A general array stays a member of its own:
 * `array<int>|array<string>` holds no array of ints and strings, so it is
 * not `array<int|string>`, and SubsumptionUnionRule drops one that another
 * member holds.
 *
 * @internal
 */
final class ShapeUnionRule implements UnionRule
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
		// the general arrays stay members of their own
		foreach ($arrays as $i => $array) {
			if ($array->getConstantArrays() !== []) {
				continue;
			}
			unset($arrays[$i]);
		}
		if ($arrays === []) {
			return $types;
		}

		$arrayList = array_values($arrays);
		// one array, already normalized as every operand is, has nothing to merge with
		if (count($arrayList) === 1 && $dropped === []) {
			return $types;
		}
		$accessoryTypes = ArrayUnion::getCommonAccessoryTypes($arrayList);
		if (count($arrayList) === 1) {
			$result = [TypeCombinator::intersect($arrayList[0], ...$accessoryTypes)];
		} else {
			$result = [];
			foreach (ArrayUnion::reduceShapes($arrayList) as $shape) {
				$result[] = ArrayUnion::withAccessoryTypes($shape, $accessoryTypes);
			}
		}

		return ArrayUnion::replaceArrayMembers($types, $arrays + $dropped, $result);
	}

}
