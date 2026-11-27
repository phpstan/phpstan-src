<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_values;
use function count;
use function get_debug_type;
use function is_float;
use function is_int;
use function is_string;
use function serialize;

/**
 * Members of one value - a constant scalar or an enum case, also refined, as
 * `'foo'&literal-string` - hold no other value, so two of them with different
 * values never merge. Of those with the same value, the one holding the other
 * stays, in the place of the first of them; of two holding each other, the
 * later one.
 *
 * @internal
 */
final class SingleValueUnionRule implements UnionRule
{

	public function getKindMask(): int
	{
		return TypeKind::ALL & ~TypeKind::ARRAY;
	}

	public function mergesConstantScalarValues(): bool
	{
		return true;
	}

	public function apply(array $types, array $normalizedGroups): array
	{
		// the index of the first member of each value: ints and strings keyed by
		// themselves, in two maps as '1' and 1 are different values, the other
		// values by getValueKey()
		/** @var array<int, int> $byInt */
		$byInt = [];
		/** @var array<string, int> $byString */
		$byString = [];
		/** @var array<string, int> $byKey */
		$byKey = [];
		// the later members of a value, by the index of the first one
		/** @var array<int, list<int>> $repeats */
		$repeats = [];
		foreach ($types as $i => $type) {
			$values = $type->getConstantScalarValues();
			if ($values !== []) {
				// bool and the unions of constants have more than one value
				if (count($values) !== 1) {
					continue;
				}
				$value = $values[0];
				if (is_int($value)) {
					if (isset($byInt[$value])) {
						$repeats[$byInt[$value]][] = $i;
					} else {
						$byInt[$value] = $i;
					}
					continue;
				}
				if (is_string($value)) {
					if (isset($byString[$value])) {
						$repeats[$byString[$value]][] = $i;
					} else {
						$byString[$value] = $i;
					}
					continue;
				}
				$key = self::getValueKey($type);
			} else {
				$key = self::getEnumCaseKey($type);
			}
			if ($key === null) {
				continue;
			}
			if (isset($byKey[$key])) {
				$repeats[$byKey[$key]][] = $i;
			} else {
				$byKey[$key] = $i;
			}
		}
		if ($repeats === []) {
			return $types;
		}

		foreach ($repeats as $first => $later) {
			$indices = [$first, ...$later];
			$count = count($indices);
			for ($x = 1; $x < $count; $x++) {
				$type = $types[$indices[$x]];
				for ($y = 0; $y < $x; $y++) {
					if (!isset($types[$indices[$y]])) {
						continue;
					}
					$other = $types[$indices[$y]];
					// a template stands for a type, not for the value of its bound
					if ($type->hasTemplateOrLateResolvableType() !== $other->hasTemplateOrLateResolvableType()) {
						continue;
					}
					if ($type->isSuperTypeOf($other)->yes()) {
						$types[$indices[$y]] = $type;
						unset($types[$indices[$x]]);
						continue 2;
					}
					if ($other->isSuperTypeOf($type)->yes()) {
						unset($types[$indices[$x]]);
						continue 2;
					}
				}
			}
		}

		return array_values($types);
	}

	/** Whether getValueKey() of the member is not null, without building the key. */
	public static function isSingleValue(Type $type): bool
	{
		if ($type->isConstantScalarValue()->yes()) {
			return count($type->getConstantScalarValues()) === 1;
		}

		return $type->getEnumCaseObject() !== null && !$type->isBoolean()->yes();
	}

	/**
	 * The value of a member that has one, the same for the members holding the
	 * same value, or null.
	 */
	public static function getValueKey(Type $type): ?string
	{
		if ($type->isConstantScalarValue()->yes()) {
			$values = $type->getConstantScalarValues();
			if (count($values) !== 1) {
				return null;
			}

			$value = $values[0];

			// the type before the value: '1', 1 and 1.0 are different values
			if (is_int($value)) {
				return 'i' . $value;
			}
			if (is_string($value)) {
				return 's' . $value;
			}

			return is_float($value) ? serialize($value) : get_debug_type($value) . ':' . $value;
		}

		return self::getEnumCaseKey($type);
	}

	private static function getEnumCaseKey(Type $type): ?string
	{
		$enumCase = $type->getEnumCaseObject();
		if ($enumCase === null || $type->isBoolean()->yes()) {
			return null;
		}

		return 'enum:' . $enumCase->getClassName() . '::' . $enumCase->getEnumCaseName();
	}

}
