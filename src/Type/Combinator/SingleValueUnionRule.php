<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_values;
use function count;
use function get_debug_type;
use function is_float;
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

	public function apply(array $types, array $normalizedGroups): array
	{
		/** @var array<string, list<int>> $byValue */
		$byValue = [];
		$hasSameValue = false;
		foreach ($types as $i => $type) {
			$key = self::getValueKey($type);
			if ($key === null) {
				continue;
			}
			$hasSameValue = $hasSameValue || isset($byValue[$key]);
			$byValue[$key][] = $i;
		}
		if (!$hasSameValue) {
			return $types;
		}

		foreach ($byValue as $indices) {
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
			return is_float($value) ? serialize($value) : get_debug_type($value) . ':' . $value;
		}

		$enumCase = $type->getEnumCaseObject();
		if ($enumCase === null || $type->isBoolean()->yes()) {
			return null;
		}

		return 'enum:' . $enumCase->getClassName() . '::' . $enumCase->getEnumCaseName();
	}

}
