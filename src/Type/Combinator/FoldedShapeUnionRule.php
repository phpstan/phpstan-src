<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeKind;
use function array_values;

/**
 * Array shapes too big to keep apart are folded before they merge exactly:
 * shapes with the same keys merge, shapes sharing a required key fold into
 * one, and what is still too big degrades to an oversized array. The arrays
 * keep the accessory types they all have. widen() runs it, union() does not.
 *
 * @internal
 */
final class FoldedShapeUnionRule implements UnionRule
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
		if ($arrays === []) {
			return $types;
		}

		$shapes = [];
		foreach ($arrays as $array) {
			$constantArrays = $array->getConstantArrays();
			// GeneralArrayUnionRule merged the arrays among which is a general one
			if ($constantArrays === []) {
				return $types;
			}
			if (!$array->isConstantArray()->yes()) {
				$shapes[] = $array;
				continue;
			}
			foreach ($constantArrays as $constantArray) {
				$shapes[] = $constantArray;
			}
		}

		$accessoryTypes = ArrayUnion::getCommonAccessoryTypes(array_values($arrays));
		$result = [];
		foreach (ArrayUnion::reduceShapes(ArrayWidening::widen($shapes)) as $shape) {
			$result[] = ArrayUnion::withAccessoryTypes($shape, $accessoryTypes);
		}

		return ArrayUnion::replaceArrayMembers($types, $arrays + $dropped, $result);
	}

}
