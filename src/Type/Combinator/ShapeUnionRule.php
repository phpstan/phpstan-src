<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeKind;
use function array_values;
use function count;

/**
 * Array shapes merge exactly where ConstantArrayType::tryUnion() merges them,
 * as `array{a: 1}|array{a: 2}` becomes `array{a: 1|2}`. The arrays keep the
 * accessory types they all have. Arrays among which is a general one are left
 * to GeneralArrayUnionRule.
 *
 * @internal
 */
final class ShapeUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::ARRAY;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		[$arrays, $dropped] = ArrayUnion::getMergeableArrays($types);
		if ($arrays === []) {
			return $types;
		}

		$arrayList = array_values($arrays);
		if (count($arrayList) > 1) {
			foreach ($arrayList as $array) {
				if ($array->getConstantArrays() === []) {
					return $types;
				}
			}
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
