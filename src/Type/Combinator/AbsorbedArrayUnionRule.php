<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\TypeKind;
use function array_values;
use function count;

/**
 * An array another member holds, such as `array<int>` in `iterable<int>`, is
 * dropped before the array rules merge the arrays into one another, which
 * would lose the rest of them to that member.
 *
 * @internal
 */
final class AbsorbedArrayUnionRule implements UnionRule
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
		$arrays = ArrayUnion::getArrayMembers($types);
		if ($arrays === [] || count($arrays) === count($types)) {
			return $types;
		}

		$holders = [];
		foreach ($types as $i => $type) {
			if (isset($arrays[$i]) || SingleValueUnionRule::isSingleValue($type)) {
				continue;
			}
			$holders[] = $type;
		}

		foreach ($arrays as $i => $array) {
			foreach ($holders as $holder) {
				if (
					!TypeKind::areDisjoint($holder->getKindMask(), $array->getKindMask())
					&& $holder->isSuperTypeOf($array)->yes()
				) {
					unset($types[$i]);
					continue 2;
				}
			}
		}

		return array_values($types);
	}

}
