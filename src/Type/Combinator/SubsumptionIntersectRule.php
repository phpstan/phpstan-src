<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\IterableType;
use PHPStan\Type\NeverType;
use PHPStan\Type\TypeKind;
use function array_splice;
use function count;

/**
 * Of two members, one holding the other is dropped, as `int&1` becomes `1`.
 * No value is of two members where neither may hold a value of the other, as
 * `int&string`.
 *
 * @internal
 */
final class SubsumptionIntersectRule implements IntersectRule
{

	public function apply(array $types): array
	{
		$count = count($types);
		for ($i = 0; $i < $count; $i++) {
			for ($j = $i + 1; $j < $count; $j++) {
				if (TypeKind::areDisjoint($types[$i]->getKindMask(), $types[$j]->getKindMask())) {
					return [new NeverType()];
				}

				// an iterable holds a mixed only when the mixed is known to be iterable
				$isSuperTypeA = $types[$j] instanceof IterableType ? $types[$j]->isSuperTypeOfMixed($types[$i]) : $types[$j]->isSuperTypeOf($types[$i]);
				if ($isSuperTypeA->yes()) {
					array_splice($types, $j--, 1);
					$count--;
					continue;
				}

				$isSuperTypeB = $types[$i] instanceof IterableType ? $types[$i]->isSuperTypeOfMixed($types[$j]) : $types[$i]->isSuperTypeOf($types[$j]);
				if ($isSuperTypeB->maybe()) {
					continue;
				}

				if ($isSuperTypeB->yes()) {
					array_splice($types, $i--, 1);
					$count--;
					continue 2;
				}

				if ($isSuperTypeA->no()) {
					return [new NeverType(reason: $isSuperTypeA->getReasons()[0] ?? null)];
				}
			}
		}

		return $types;
	}

}
