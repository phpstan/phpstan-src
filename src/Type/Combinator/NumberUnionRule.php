<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\NumberSet;
use PHPStan\Type\ScalarUnion;
use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_diff_key;
use function array_key_first;
use function array_keys;
use function array_push;
use function array_values;
use function count;
use const INF;

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

	public function mergesConstantScalarValues(): bool
	{
		return false;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		$candidates = [];
		$kinds = [];
		foreach ($types as $i => $type) {
			$kind = $type->getKindMask();
			if (($kind & ~(TypeKind::INT | TypeKind::FLOAT)) !== 0) {
				continue;
			}
			$candidates[$i] = $type;
			$kinds[$i] = $kind;
		}
		if (count($candidates) < 2) {
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

		$absorbed = self::findAbsorbed($numberTypes, $kinds);
		if ($absorbed !== null) {
			return array_values(array_diff_key($types, $absorbed));
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

	/**
	 * The members a member holding every number of their kind holds, as `int`
	 * holds `0` and `int<1, 5>`, and `float` (NAN included) holds `1.5` - when
	 * no more than one member of each kind is left besides, so nothing merges
	 * and the sweep of intervals has nothing to do. Null otherwise.
	 *
	 * @param array<int, array{Type, NumberSet}> $numberTypes
	 * @param array<int, int> $kinds the kind masks of the members
	 * @return array<int, true>|null
	 */
	private static function findAbsorbed(array $numberTypes, array $kinds): ?array
	{
		$whole = [];
		foreach ($numberTypes as $i => [, $numbers]) {
			if ($kinds[$i] === TypeKind::INT) {
				if (!isset($whole[TypeKind::INT]) && $numbers->getIntegerIntervals() === [[null, null]]) {
					$whole[TypeKind::INT] = $i;
				}
			} elseif ($kinds[$i] === TypeKind::FLOAT) {
				if (!isset($whole[TypeKind::FLOAT]) && $numbers->hasNan() && $numbers->getFloatIntervals() === [[-INF, INF]]) {
					$whole[TypeKind::FLOAT] = $i;
				}
			} else {
				return null;
			}
		}
		if ($whole === []) {
			return null;
		}

		$absorbed = [];
		$left = [TypeKind::INT => 0, TypeKind::FLOAT => 0];
		foreach (array_keys($numberTypes) as $i) {
			$kind = $kinds[$i];
			if (isset($whole[$kind]) && $whole[$kind] !== $i) {
				$absorbed[$i] = true;
				continue;
			}
			$left[$kind]++;
		}
		if ($left[TypeKind::INT] > 1 || $left[TypeKind::FLOAT] > 1) {
			return null;
		}

		return $absorbed;
	}

}
