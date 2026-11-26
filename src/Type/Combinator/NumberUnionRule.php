<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\ScalarUnion;
use PHPStan\Type\TypeKind;
use function array_key_first;
use function array_push;
use function array_values;
use function count;

/**
 * Ints, floats and their ranges and constants - the members whose
 * toExactNumberSet() is not null - merge by their sets of numbers: touching or
 * overlapping intervals become one range, as `int<0, 5>|int<6, max>` becomes
 * `int<0, max>`. They take the place of the first of them.
 *
 * @internal
 */
final class NumberUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::INT | TypeKind::FLOAT;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		$candidates = [];
		// constants alone stay apart, SingleValueUnionRule merged the equal ones
		$hasNonConstant = false;
		foreach ($types as $i => $type) {
			if (($type->getKindMask() & ~(TypeKind::INT | TypeKind::FLOAT)) !== 0) {
				continue;
			}
			$candidates[$i] = $type;
			$hasNonConstant = $hasNonConstant || !$type->isConstantScalarValue()->yes();
		}
		if (count($candidates) < 2 || !$hasNonConstant) {
			return $types;
		}

		$numberTypes = [];
		foreach ($candidates as $i => $type) {
			$numbers = $type->toExactNumberSet();
			if ($numbers === null) {
				continue;
			}
			$numberTypes[$i] = [$type, $numbers];
		}
		if (count($numberTypes) < 2) {
			return $types;
		}

		$firstIndex = array_key_first($numberTypes);
		[$ranges, $constants] = ScalarUnion::unionNumbers(array_values($numberTypes));
		$result = [];
		foreach ($types as $i => $type) {
			if ($i === $firstIndex) {
				array_push($result, ...$ranges, ...$constants);
				continue;
			}
			if (isset($numberTypes[$i])) {
				continue;
			}
			$result[] = $type;
		}

		return $result;
	}

}
