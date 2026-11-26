<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeKind;
use function array_splice;
use function count;

/**
 * Two members whose tryIntersect() is a single type are replaced by it, as
 * `array{a?: int}&hasOffset('a')` becomes `array{a: int}`. No value is of two
 * members of disjoint kinds.
 *
 * Exact merges run before SubsumptionIntersectRule: a member that rule drops
 * as redundant, such as a HasPropertyType next to stdClass, can still sharpen
 * another one, such as the object shape it makes a property of required.
 * Which of the two reaches the pair first would depend on the order of the
 * members, and the intersection must not.
 *
 * @internal
 */
final class ExactIntersectRule implements IntersectRule
{

	public function apply(array $types): array
	{
		$count = count($types);
		for ($i = 0; $i < $count; $i++) {
			for ($j = $i + 1; $j < $count; $j++) {
				if (TypeKind::areDisjoint($types[$i]->getKindMask(), $types[$j]->getKindMask())) {
					return [new NeverType()];
				}

				$merged = $types[$j]->tryIntersect($types[$i]) ?? $types[$i]->tryIntersect($types[$j]);
				if ($merged === $types[$i]) {
					// the member at $i holds the other one, and the members
					// before $j were already paired with it
					array_splice($types, $j--, 1);
					$count--;
					continue;
				}
				if ($merged !== null) {
					array_splice($types, $j, 1);
					array_splice($types, $i, 1, self::members($merged));
					$count = count($types);
					$i--;
					continue 2;
				}
			}
		}

		return $types;
	}

	/** @return list<Type> */
	private static function members(Type $type): array
	{
		return $type instanceof IntersectionType && !$type instanceof TemplateType ? $type->getTypes() : [$type];
	}

}
