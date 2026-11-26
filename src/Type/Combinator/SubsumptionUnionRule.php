<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use PHPStan\Type\VerbosityLevel;
use function array_filter;
use function array_keys;
use function array_values;
use function count;
use function ksort;
use function range;
use function spl_object_id;

/**
 * Of two members, one holding the other takes its place, and one whose
 * tryUnion() with the other is a single type is replaced by it, as
 * `mixed~int|int` becomes `mixed`. Pairs of single values never merge -
 * SingleValueUnionRule merged those of the same value - and neither do two
 * shapes, two numbers or two string refinements, which the rules before
 * merged, a member of one of these classes and one of another, or two
 * members of a union passed in.
 *
 * @internal
 */
final class SubsumptionUnionRule implements UnionRule
{

	/** Above this many members of more than one value, the ones described the same are merged first. */
	private const DESCRIBED_MEMBERS_LIMIT = 16;

	private const SINGLE_VALUE = 0;

	private const SHAPE = 1;

	private const NUMBERS = 2;

	private const STRING_REFINEMENT = 3;

	/** A member of none of the classes above, paired with every other member. */
	private const OTHER = 4;

	private const IN_NO_GROUP = -1;

	private const IN_GROUPS = -2;

	public function getKindMask(): int
	{
		return TypeKind::ALL;
	}

	public function mergesConstantScalarValues(): bool
	{
		return false;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		if (count($types) < 2) {
			return $types;
		}

		$isSingleValue = [];
		foreach ($types as $i => $type) {
			$isSingleValue[$i] = SingleValueUnionRule::isSingleValue($type);
		}
		if (count($types) - count(array_filter($isSingleValue)) > self::DESCRIBED_MEMBERS_LIMIT) {
			[$types, $isSingleValue] = self::mergeDescribedSame($types, $isSingleValue);
		}
		$classes = [];
		foreach ($types as $i => $type) {
			$classes[$i] = $isSingleValue[$i] ? self::SINGLE_VALUE : self::getClass($type);
		}

		// the other members: a member of another class is paired with these only
		$others = [];
		foreach ($classes as $i => $class) {
			if ($class !== self::OTHER) {
				continue;
			}
			$others[$i] = true;
		}
		if ($others === []) {
			// no pair has a member of the other class
			return $types;
		}

		// the input unions keep their members alive, so their ids stay unique
		$groupsByType = [];
		$inTwoGroups = [];
		foreach ($normalizedGroups as $group => $members) {
			foreach ($members as $member) {
				$id = spl_object_id($member);
				if (isset($groupsByType[$id][$group])) {
					$inTwoGroups[$id] = true;
					continue;
				}
				$groupsByType[$id][$group] = true;
			}
		}

		$groupIds = [];
		foreach ($types as $i => $type) {
			$groupIds[$i] = self::getGroupId($groupsByType, $inTwoGroups, $type);
		}

		$count = count($types);
		for ($i = 0; $i < $count; $i++) {
			if (!isset($types[$i])) {
				continue;
			}
			foreach ($classes[$i] !== self::OTHER ? array_keys($others) : range($i + 1, $count - 1) as $j) {
				if ($j <= $i || !isset($types[$j])) {
					continue;
				}
				if ($classes[$i] !== self::OTHER && $classes[$j] !== self::OTHER) {
					continue;
				}
				if (!$isSingleValue[$i] && !$isSingleValue[$j]) {
					$iGroup = $groupIds[$i];
					$jGroup = $groupIds[$j];
					if ($types[$i] === $types[$j] || $iGroup === self::IN_GROUPS || $jGroup === self::IN_GROUPS) {
						if (self::isAlreadyNormalized($groupsByType, $inTwoGroups, $types[$i], $types[$j])) {
							continue;
						}
					} elseif ($iGroup !== self::IN_NO_GROUP && $iGroup === $jGroup) {
						continue;
					}
				}

				$merged = self::merge($types[$i], $types[$j]);
				if ($merged === null) {
					continue;
				}

				[$a, $b] = $merged;
				if ($a !== null) {
					$types[$i] = $a;
					$groupIds[$i] = self::getGroupId($groupsByType, $inTwoGroups, $a);
					unset($types[$j], $others[$j]);
					if ($classes[$i] !== self::OTHER && self::getMergedClass($a) === self::OTHER) {
						// the members compared with the others only meet this one from the start
						$isSingleValue[$i] = false;
						$classes[$i] = self::OTHER;
						$others[$i] = true;
						ksort($others);
						$i = -1;
						continue 2;
					}
					continue;
				}
				if ($b === null) {
					continue;
				}

				$types[$j] = $b;
				$groupIds[$j] = self::getGroupId($groupsByType, $inTwoGroups, $b);
				unset($types[$i], $others[$i]);
				if ($classes[$j] !== self::OTHER && self::getMergedClass($b) === self::OTHER) {
					$isSingleValue[$j] = false;
					$classes[$j] = self::OTHER;
					$others[$j] = true;
					ksort($others);
					$i = -1;
				}
				continue 2;
			}
		}

		return array_values($types);
	}

	/**
	 * The members of a class other than OTHER were merged among themselves by
	 * an earlier rule: SingleValueUnionRule, ShapeUnionRule, NumberUnionRule
	 * or StringUnionRule, which also dropped the single values the numbers and
	 * the string refinements hold.
	 *
	 * @return self::SHAPE|self::NUMBERS|self::STRING_REFINEMENT|self::OTHER
	 */
	private static function getClass(Type $type): int
	{
		if ($type->isConstantArray()->yes()) {
			return self::SHAPE;
		}
		if (($type->getKindMask() & ~(TypeKind::INT | TypeKind::FLOAT)) === 0 && $type->toExactNumberSet() !== null) {
			return self::NUMBERS;
		}
		if ($type->getKindMask() === TypeKind::STRING && $type->getStringRefinement() !== null) {
			return self::STRING_REFINEMENT;
		}

		return self::OTHER;
	}

	/**
	 * The class of what a member merged into.
	 *
	 * @return self::SINGLE_VALUE|self::SHAPE|self::NUMBERS|self::STRING_REFINEMENT|self::OTHER
	 */
	private static function getMergedClass(Type $merged): int
	{
		return SingleValueUnionRule::isSingleValue($merged) ? self::SINGLE_VALUE : self::getClass($merged);
	}

	/**
	 * The one normalized group of a member, IN_NO_GROUP, or IN_GROUPS for a
	 * member of several groups or of one twice, left to isAlreadyNormalized().
	 *
	 * @param array<int, array<int, true>> $groupsByType
	 * @param array<int, true> $inTwoGroups
	 */
	private static function getGroupId(array $groupsByType, array $inTwoGroups, Type $type): int
	{
		$id = spl_object_id($type);
		$groups = $groupsByType[$id] ?? null;
		if ($groups === null) {
			return self::IN_NO_GROUP;
		}
		if (count($groups) > 1 || isset($inTwoGroups[$id])) {
			return self::IN_GROUPS;
		}

		foreach (array_keys($groups) as $group) {
			return $group;
		}

		return self::IN_NO_GROUP;
	}

	/**
	 * @param array<int, array<int, true>> $groupsByType
	 * @param array<int, true> $inTwoGroups
	 */
	private static function isAlreadyNormalized(array $groupsByType, array $inTwoGroups, Type $a, Type $b): bool
	{
		$aId = spl_object_id($a);
		if ($a === $b) {
			return isset($inTwoGroups[$aId]);
		}

		$aGroups = $groupsByType[$aId] ?? null;
		$bGroups = $groupsByType[spl_object_id($b)] ?? null;
		if ($aGroups === null || $bGroups === null) {
			return false;
		}

		foreach (array_keys($aGroups) as $group) {
			if (isset($bGroups[$group])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{Type, null}|array{null, Type}|null
	 */
	private static function merge(Type $a, Type $b): ?array
	{
		// values of different kinds never merge into one member
		if (TypeKind::areDisjoint($a->getKindMask(), $b->getKindMask())) {
			return null;
		}

		// ShapeUnionRule merged the shapes that merge
		if ($a->isConstantArray()->yes() && $b->isConstantArray()->yes()) {
			return null;
		}

		$union = $a->tryUnion($b);
		if ($union !== null) {
			return [$union, null];
		}

		$union = $b->tryUnion($a);
		if ($union !== null) {
			return [null, $union];
		}

		if ($b->isSuperTypeOf($a)->yes()) {
			return [null, $b];
		}

		if ($a->isSuperTypeOf($b)->yes()) {
			return [$a, null];
		}

		return null;
	}

	/**
	 * @param list<Type> $types
	 * @param array<int, bool> $isSingleValue
	 * @return array{list<Type>, list<bool>}
	 */
	private static function mergeDescribedSame(array $types, array $isSingleValue): array
	{
		$positions = [];
		foreach ($types as $i => $type) {
			if ($isSingleValue[$i]) {
				continue;
			}
			$key = $type->describe(VerbosityLevel::cache());
			if (isset($positions[$key])) {
				$types[$positions[$key]] = $type;
				unset($types[$i], $isSingleValue[$i]);
				continue;
			}
			$positions[$key] = $i;
		}

		return [array_values($types), array_values($isSingleValue)];
	}

}
