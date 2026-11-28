<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_keys;
use function array_pop;
use function array_values;
use function count;

/**
 * With bleeding edge, a general array merges with the arrays it cannot be told
 * apart from by the kinds of their keys or values, as
 * `array<string, string>|array{foo: string}` becomes `array<string, string>`.
 * Arrays whose keys or values are of
 * kinds that share no value stay members of their own: `array<int>|array<string>`
 * holds no array of ints and strings, so it is not `array<int|string>`.
 * Shapes among themselves are left to ShapeUnionRule.
 *
 * Telling arrays apart only by kinds keeps a union small: there are only so
 * many kinds, so the members cannot multiply level by level in nested arrays.
 *
 * @internal
 */
final class OverlappingArrayUnionRule implements UnionRule
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

		$generalArrays = [];
		$shapes = [];
		foreach ($arrays as $i => $array) {
			if (self::isItselfTemplateOrLateResolvable($array)) {
				unset($arrays[$i]);
				continue;
			}
			if ($array->getConstantArrays() !== []) {
				$shapes[$i] = $array;
				continue;
			}
			$generalArrays[$i] = $array;
		}
		if ($generalArrays === [] || count($arrays) < 2) {
			return $types;
		}

		// the kinds of the keys and the values, which every pair compares
		$masks = [];
		foreach ($arrays as $i => $array) {
			$masks[$i] = self::getKindMasks($array);
		}

		// the general arrays connected by not being told apart
		$groups = [];
		$unvisited = $generalArrays;
		foreach (array_keys($generalArrays) as $root) {
			if (!isset($unvisited[$root])) {
				continue;
			}
			unset($unvisited[$root]);
			$group = [$root => $arrays[$root]];
			$queue = [$root];
			while ($queue !== []) {
				$i = array_pop($queue);
				foreach (array_keys($unvisited) as $j) {
					if (self::areToldApart($arrays[$i], $masks[$i], $arrays[$j], $masks[$j])) {
						continue;
					}
					unset($unvisited[$j]);
					$group[$j] = $arrays[$j];
					$queue[] = $j;
				}
			}
			$groups[] = $group;
		}

		// the shapes connected to each group, found from its general arrays,
		// so the shapes no group reaches are not compared among themselves
		$reachingGroups = [];
		foreach ($groups as $g => $group) {
			$reached = [];
			$queue = array_keys($group);
			while ($queue !== []) {
				$i = array_pop($queue);
				foreach ($shapes as $j => $shape) {
					if (isset($reached[$j]) || self::areToldApart($arrays[$i], $masks[$i], $shape, $masks[$j])) {
						continue;
					}
					$reached[$j] = true;
					$queue[] = $j;
				}
			}
			foreach (array_keys($reached) as $j) {
				$reachingGroups[$j][] = $g;
			}
		}

		// a shape reached from two groups stays a member of its own: merged
		// with both, it would merge general arrays told apart, as
		// list<int>|list<string>|array{int|string} would become list<int|string>
		foreach ($reachingGroups as $j => $reachingGroupIndices) {
			if (count($reachingGroupIndices) !== 1) {
				continue;
			}
			$groups[$reachingGroupIndices[0]][$j] = $shapes[$j];
		}

		$replaced = [];
		$merged = [];
		foreach ($groups as $members) {
			if (count($members) < 2) {
				continue;
			}
			$replaced += $members;
			$merged[] = ArrayUnion::mergeIntoGeneralArray(array_values($members));
		}
		if ($merged === []) {
			return $types;
		}

		return ArrayUnion::replaceArrayMembers($types, $replaced + $dropped, $merged);
	}

	/**
	 * A template type such as `T of array` merged into a general array would
	 * be lost; templates in its keys and values are kept by the merge.
	 */
	private static function isItselfTemplateOrLateResolvable(Type $array): bool
	{
		return $array->hasTemplateOrLateResolvableType()
			&& !$array->getIterableKeyType()->hasTemplateOrLateResolvableType()
			&& !$array->getIterableValueType()->hasTemplateOrLateResolvableType();
	}

	/**
	 * The kinds of the keys and of the values of the arrays the type is made
	 * of. They are those of its keys and values, or more when an accessory
	 * type narrows them, which only makes the arrays less often told apart.
	 *
	 * @return array{int, int}
	 */
	private static function getKindMasks(Type $array): array
	{
		$keyMask = 0;
		$valueMask = 0;
		foreach ($array->getArrays() as $plainArray) {
			$keyMask |= $plainArray->getIterableKeyType()->getKindMask();
			$valueMask |= $plainArray->getIterableValueType()->getKindMask();
		}

		return [$keyMask, $valueMask];
	}

	/**
	 * Whether the keys or the values of two arrays are of kinds that share no
	 * value, or their values are arrays told apart so.
	 *
	 * @param array{int, int} $aMasks
	 * @param array{int, int} $bMasks
	 */
	private static function areToldApart(Type $a, array $aMasks, Type $b, array $bMasks): bool
	{
		if (TypeKind::areDisjoint($aMasks[0], $bMasks[0]) || TypeKind::areDisjoint($aMasks[1], $bMasks[1])) {
			return true;
		}
		if ($aMasks[1] !== TypeKind::ARRAY || $bMasks[1] !== TypeKind::ARRAY) {
			return false;
		}

		$bArrays = self::getValueArrays($b);
		foreach (self::getValueArrays($a) as $aArray) {
			$aArrayMasks = self::getKindMasks($aArray);
			foreach ($bArrays as $bArray) {
				if (!self::areToldApart($aArray, $aArrayMasks, $bArray, self::getKindMasks($bArray))) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * The arrays in the values of the arrays the type is made of, as in
	 * getKindMasks().
	 *
	 * @return list<Type>
	 */
	private static function getValueArrays(Type $array): array
	{
		$valueArrays = [];
		foreach ($array->getArrays() as $plainArray) {
			foreach ($plainArray->getIterableValueType()->getArrays() as $valueArray) {
				$valueArrays[] = $valueArray;
			}
		}

		return $valueArrays;
	}

}
