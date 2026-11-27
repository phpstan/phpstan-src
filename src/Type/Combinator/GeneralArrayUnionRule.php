<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_keys;
use function count;
use function decbin;
use function substr_count;

/**
 * General arrays kept apart because their keys or values are of kinds that
 * share no value are bounded in number: while there are more than
 * GENERAL_ARRAYS_LIMIT of them, the two whose kinds differ the least merge, as
 * `array<int>|array<string>` becomes `array<int|string>`. This loses which
 * kinds go together. The merged values widen too, so arrays nested in them
 * are bounded the same way.
 *
 * @internal
 */
final class GeneralArrayUnionRule implements UnionRule
{

	public const GENERAL_ARRAYS_LIMIT = 8;

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
		$arrays = self::getGeneralArrays($types);
		if (count($arrays) <= self::GENERAL_ARRAYS_LIMIT) {
			return $types;
		}

		$masks = [];
		foreach ($arrays as $i => $array) {
			$masks[$i] = self::getKindMasks($array);
		}

		$replaced = [];
		while (count($arrays) > self::GENERAL_ARRAYS_LIMIT) {
			$closest = null;
			$closestDistance = null;
			foreach (array_keys($arrays) as $i) {
				foreach (array_keys($arrays) as $j) {
					if ($j <= $i) {
						continue;
					}
					$distance = self::countBits($masks[$i][0] ^ $masks[$j][0]) + self::countBits($masks[$i][1] ^ $masks[$j][1]);
					if ($closestDistance !== null && $distance >= $closestDistance) {
						continue;
					}
					$closest = [$i, $j];
					$closestDistance = $distance;
				}
			}
			if ($closest === null) {
				break;
			}

			[$i, $j] = $closest;
			$replaced[$i] = true;
			$replaced[$j] = true;
			$arrays[$i] = ArrayUnion::mergeIntoGeneralArray([$arrays[$i], $arrays[$j]], true);
			$masks[$i] = self::getKindMasks($arrays[$i]);
			unset($arrays[$j], $masks[$j]);
		}

		$merged = [];
		foreach ($arrays as $i => $array) {
			if (!isset($replaced[$i])) {
				continue;
			}
			$merged[] = $array;
		}

		return ArrayUnion::replaceArrayMembers($types, $replaced, $merged);
	}

	/**
	 * @param list<Type> $types
	 * @return array<int, Type>
	 */
	public static function getGeneralArrays(array $types): array
	{
		[$arrays] = ArrayUnion::getMergeableArrays($types);
		foreach ($arrays as $i => $array) {
			if ($array->getConstantArrays() === [] && !$array->hasTemplateOrLateResolvableType()) {
				continue;
			}
			unset($arrays[$i]);
		}

		return $arrays;
	}

	/** @return array{int, int} */
	private static function getKindMasks(Type $array): array
	{
		return [$array->getIterableKeyType()->getKindMask(), $array->getIterableValueType()->getKindMask()];
	}

	private static function countBits(int $mask): int
	{
		return substr_count(decbin($mask), '1');
	}

}
