<?php declare(strict_types = 1);

namespace PHPStan\Type;

use function array_keys;
use function count;
use function is_int;
use function ksort;
use function max;
use function min;
use function pack;
use function usort;
use const INF;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The union of the scalar members of a union, kind by kind: the numbers as
 * intervals, the strings as StringRefinement cells. Each kind is exact - the
 * result holds the values of the members and nothing more - so the result does
 * not depend on the order of the members.
 *
 * @internal
 */
final class ScalarUnion
{

	/**
	 * Merges the members whose toExactNumberSet() is not null: touching or
	 * overlapping intervals of which at least one is a range become one range,
	 * constants that meet no range stay constants, as 1|2|3 does.
	 *
	 * @param list<array{Type, NumberSet}> $members each member with its toExactNumberSet()
	 * @return array{list<Type>, list<Type>} the ranges, then the constants left in the order they came
	 */
	public static function unionNumbers(array $members): array
	{
		$integers = [];
		$floats = [];
		$nans = [];
		$nanAbsorbed = false;
		foreach ($members as $index => [$type, $numbers]) {
			foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
				$integers[] = [$min, $max, $type, $index];
			}
			$floatIntervals = $numbers->getFloatIntervals();
			foreach ($floatIntervals as [$min, $max]) {
				$floats[] = [FloatRangeType::toOrdinal($min), FloatRangeType::toOrdinal($max), $type, $index];
			}
			if (!$numbers->hasNan()) {
				continue;
			}

			if ($floatIntervals === []) {
				$nans[$index] = $type;
			} else {
				// float holds NAN next to its range
				$nanAbsorbed = true;
			}
		}

		[$integerRanges, $integerConstants] = self::mergeIntervals(
			$integers,
			static fn (?int $max, ?int $min): bool => $max === null || $min === null || $min - 1 <= $max,
			static fn (?int $min, ?int $max): Type => IntegerRangeType::fromInterval($min, $max),
			static fn (array $item): string => (string) $item[0],
		);
		[$floatRanges, $floatConstants] = self::mergeIntervals(
			$floats,
			static fn (int $max, int $min): bool => $min - 1 <= $max,
			static fn (int $min, int $max): Type => FloatRangeType::fromInterval(FloatRangeType::fromOrdinal($min), FloatRangeType::fromOrdinal($max)),
			// both zeros are one ordinal, but two constants
			static fn (array $item): string => pack('d', $item[2]->getConstantScalarValues()[0]),
		);

		$ranges = $integerRanges;
		foreach ($floatRanges as $floatRange) {
			// float<-INF, INF> and NAN make float
			if ($nans !== [] || $nanAbsorbed) {
				$numbers = $floatRange->toExactNumberSet();
				if ($numbers !== null && $numbers->getFloatIntervals() === [[-INF, INF]]) {
					$floatRange = $numbers->hasNan() ? $floatRange : new FloatType();
					$nans = [];
				}
			}
			$ranges[] = $floatRange;
		}

		$constants = $integerConstants + $floatConstants;
		if (!$nanAbsorbed && $nans !== []) {
			$constants[array_keys($nans)[0]] = $nans[array_keys($nans)[0]];
		}
		ksort($constants);

		return [$ranges, self::values($constants)];
	}

	/**
	 * @template T of int|null
	 * @param list<array{T, T, Type, int}> $items
	 * @param callable(T, T): bool $touches whether an interval starting at the second bound meets one ending at the first
	 * @param callable(T, T): Type $create
	 * @param callable(array{T, T, Type, int}): string $constantKey
	 * @return array{list<Type>, array<int, Type>}
	 */
	private static function mergeIntervals(array $items, callable $touches, callable $create, callable $constantKey): array
	{
		if ($items === []) {
			return [[], []];
		}

		// A range repeated adds nothing: sorted after the first one of its
		// bounds, it never takes that one's place. A constant stays, both
		// zeros being one ordinal but two constants.
		$seenBounds = [];
		foreach ($items as $k => $item) {
			if ($item[0] !== null && $item[0] === $item[1]) {
				continue;
			}
			$bounds = ($item[0] ?? 'min') . ':' . ($item[1] ?? 'max');
			if (isset($seenBounds[$bounds])) {
				unset($items[$k]);
				continue;
			}
			$seenBounds[$bounds] = true;
		}

		usort($items, static fn (array $a, array $b): int => self::compareBound($a[0], $b[0]) ?: $a[3] <=> $b[3]);

		$ranges = [];
		$constants = [];
		$group = [];
		$groupMax = null;
		$flush = static function () use (&$group, &$ranges, &$constants, $create, $constantKey): void {
			$rangeItems = [];
			foreach ($group as $item) {
				if ($item[0] !== null && $item[0] === $item[1]) {
					continue;
				}
				$rangeItems[] = $item;
			}

			if ($rangeItems === []) {
				$seen = [];
				foreach ($group as $item) {
					$key = $constantKey($item);
					if (isset($seen[$key])) {
						continue;
					}
					$seen[$key] = true;
					$constants[$item[3]] = $item[2];
				}
				return;
			}

			$min = $group[0][0];
			$max = $group[0][1];
			foreach ($group as $item) {
				if ($item[1] !== null && ($max === null || $item[1] <= $max)) {
					continue;
				}
				$max = $item[1];
			}

			// a range that covers its group stays the same object
			foreach ($rangeItems as $item) {
				if ($item[0] === $min && $item[1] === $max) {
					$ranges[] = $item[2];
					return;
				}
			}
			$ranges[] = $create($min, $max);
		};

		foreach ($items as $item) {
			if ($group !== [] && !$touches($groupMax, $item[0])) {
				$flush();
				$group = [];
			}
			if ($group === [] || ($groupMax !== null && ($item[1] === null || $item[1] > $groupMax))) {
				$groupMax = $item[1];
			}
			$group[] = $item;
		}
		$flush();

		return [$ranges, $constants];
	}

	/**
	 * The intersection of operands that are each ints, integer ranges and
	 * integer constants - the members of each given as a list, a union by its
	 * members - as the ranges and constants of the integers all of them hold.
	 * The intervals of two operands are swept in the order of their bounds,
	 * instead of intersecting each member with each.
	 *
	 * @param non-empty-list<list<Type>> $operands
	 * @return list<Type>|null null when a member is not of exact integers
	 */
	public static function intersectIntegers(array $operands): ?array
	{
		$intervals = null;
		foreach ($operands as $members) {
			$operandIntervals = self::getIntegerIntervals($members);
			if ($operandIntervals === null) {
				return null;
			}
			$intervals = $intervals === null ? $operandIntervals : self::intersectIntervals($intervals, $operandIntervals);
		}

		return self::createIntegers($intervals);
	}

	/**
	 * The integers of the members of one operand without those of the members
	 * of the other, as ranges and constants, both swept in the order of their
	 * bounds.
	 *
	 * @param list<Type> $members
	 * @param list<Type> $membersToRemove
	 * @return list<Type>|null null when a member is not of exact integers
	 */
	public static function removeIntegers(array $members, array $membersToRemove): ?array
	{
		$intervals = self::getIntegerIntervals($members);
		if ($intervals === null) {
			return null;
		}
		$intervalsToRemove = self::getIntegerIntervals($membersToRemove);
		if ($intervalsToRemove === null) {
			return null;
		}

		$result = [];
		$j = 0;
		foreach ($intervals as [$min, $max]) {
			while ($j < count($intervalsToRemove) && $intervalsToRemove[$j][1] < $min) {
				$j++;
			}
			// the removed intervals from $j on overlap this one or come after it
			for ($k = $j; $k < count($intervalsToRemove) && $intervalsToRemove[$k][0] <= $max; $k++) {
				[$removedMin, $removedMax] = $intervalsToRemove[$k];
				if ($removedMin > $min) {
					$result[] = [$min, self::predecessor($removedMin)];
				}
				$min = self::successor($removedMax);
			}
			if ($min > $max) {
				continue;
			}
			$result[] = [$min, $max];
		}

		return self::createIntegers($result);
	}

	/**
	 * The integer intervals of the members, sorted and with the overlapping
	 * ones joined. An unbounded side is an infinity.
	 *
	 * @param list<Type> $members
	 * @return list<array{int|float, int|float}>|null
	 */
	private static function getIntegerIntervals(array $members): ?array
	{
		$intervals = [];
		foreach ($members as $member) {
			$numbers = $member->getKindMask() === TypeKind::INT ? $member->toExactNumberSet() : null;
			if ($numbers === null) {
				return null;
			}
			foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
				$intervals[] = [$min ?? -INF, $max ?? INF];
			}
		}

		return self::coalesceIntervals($intervals);
	}

	/** The next integer, or INF after the biggest one. */
	private static function successor(int|float $bound): int|float
	{
		return is_int($bound) && $bound < PHP_INT_MAX ? $bound + 1 : INF;
	}

	/** The previous integer, or -INF before the smallest one. */
	private static function predecessor(int|float $bound): int|float
	{
		return is_int($bound) && $bound > PHP_INT_MIN ? $bound - 1 : -INF;
	}

	/**
	 * @param list<array{int|float, int|float}> $intervals
	 * @return list<Type>
	 */
	private static function createIntegers(array $intervals): array
	{
		$types = [];
		foreach ($intervals as [$min, $max]) {
			// past either end of the integers, nothing is left of the interval
			if ($min === INF || $max === -INF) {
				continue;
			}
			$types[] = IntegerRangeType::fromInterval(is_int($min) ? $min : null, is_int($max) ? $max : null);
		}

		return $types;
	}

	/**
	 * The intervals sorted by their lower bounds, overlapping ones joined.
	 *
	 * @param list<array{int|float, int|float}> $intervals
	 * @return list<array{int|float, int|float}>
	 */
	private static function coalesceIntervals(array $intervals): array
	{
		usort($intervals, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
		$coalesced = [];
		foreach ($intervals as $interval) {
			$last = count($coalesced) - 1;
			if ($last >= 0 && $interval[0] <= $coalesced[$last][1]) {
				$coalesced[$last][1] = max($coalesced[$last][1], $interval[1]);
				continue;
			}
			$coalesced[] = $interval;
		}

		return $coalesced;
	}

	/**
	 * @param list<array{int|float, int|float}> $left sorted and disjoint
	 * @param list<array{int|float, int|float}> $right sorted and disjoint
	 * @return list<array{int|float, int|float}> sorted and disjoint
	 */
	private static function intersectIntervals(array $left, array $right): array
	{
		$intervals = [];
		$i = 0;
		$j = 0;
		while ($i < count($left) && $j < count($right)) {
			$min = max($left[$i][0], $right[$j][0]);
			$max = min($left[$i][1], $right[$j][1]);
			if ($min <= $max) {
				$intervals[] = [$min, $max];
			}
			// the interval ending first meets no later interval of the other side
			if ($left[$i][1] < $right[$j][1]) {
				$i++;
			} else {
				$j++;
			}
		}

		return $intervals;
	}

	/** Orders the lower bounds, null being the smallest. */
	private static function compareBound(?int $a, ?int $b): int
	{
		if ($a === null || $b === null) {
			return ($a === null ? 0 : 1) <=> ($b === null ? 0 : 1);
		}

		return $a <=> $b;
	}

	/**
	 * Merges the members whose getStringRefinement() is not null with each other,
	 * removes the constant strings their cells hold, and merges the empty string
	 * and '0' into them where one refinement holds both.
	 *
	 * @param list<Type> $refinements
	 * @param list<Type> $constantStrings
	 * @return array{list<Type>, list<Type>} the refinements, then the constants left
	 */
	public static function unionStrings(array $refinements, array $constantStrings): array
	{
		/** @var list<array{int, Type, false}|array{int<1, StringRefinement::VALUES>, Type, true}> $items cells, type, whether it is a constant */
		$items = [];
		foreach ($refinements as $refinement) {
			$items[] = [$refinement->getStringRefinement() ?? StringRefinement::ALL, $refinement, false];
		}

		$held = 0;
		foreach ($items as [$cells]) {
			$held |= $cells;
		}

		$constants = [];
		foreach ($constantStrings as $constant) {
			$cells = StringRefinement::ofConstant($constant->getConstantStrings()[0]);
			if (($held & $cells) === $cells && self::isHeldByOne($items, $cells)) {
				continue;
			}
			if ($items !== [] && StringRefinement::isSingleValue($cells)) {
				$items[] = [$cells, $constant, true];
				continue;
			}
			$constants[] = $constant;
		}

		$count = count($items);
		for ($i = 0; $i < $count; $i++) {
			if (!isset($items[$i])) {
				continue;
			}
			for ($j = $i + 1; $j < $count; $j++) {
				if (!isset($items[$j])) {
					continue;
				}
				$union = $items[$i][0] | $items[$j][0];
				if ($union === $items[$i][0] && !$items[$i][2]) {
					// the member at $i takes the other in, and the members
					// before $j were already paired with it
					unset($items[$j]);
					continue;
				} elseif ($union === $items[$j][0] && !$items[$j][2]) {
					$merged = $items[$j];
				} else {
					$type = StringRefinement::toType($union);
					if ($type === null && ($items[$i][2] || $items[$j][2])) {
						// the value of a constant is its value whether a string holding it is literal or not
						$union = self::widenConstant($items[$i]) | self::widenConstant($items[$j]);
						$type = StringRefinement::toType($union);
					}
					if ($type === null) {
						continue;
					}
					$merged = [$union, $type, false];
				}

				$items[$i] = $merged;
				unset($items[$j]);
				// the merged member may now merge with one before it
				$i = -1;
				break;
			}
		}

		$result = [];
		foreach ($items as [, $type, $isConstant]) {
			if ($isConstant) {
				$constants[] = $type;
				continue;
			}
			$result[] = $type;
		}

		return [$result, $constants];
	}

	/** @param array{int, Type, false}|array{int<1, StringRefinement::VALUES>, Type, true} $item */
	private static function widenConstant(array $item): int
	{
		return $item[2] ? StringRefinement::of($item[0]) : $item[0];
	}

	/** @param list<array{int, Type, false}|array{int<1, StringRefinement::VALUES>, Type, true}> $items */
	private static function isHeldByOne(array $items, int $cells): bool
	{
		foreach ($items as [$itemCells]) {
			if (($itemCells & $cells) === $cells) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @template T
	 * @param array<T> $values
	 * @return list<T>
	 */
	private static function values(array $values): array
	{
		$list = [];
		foreach ($values as $value) {
			$list[] = $value;
		}

		return $list;
	}

}
