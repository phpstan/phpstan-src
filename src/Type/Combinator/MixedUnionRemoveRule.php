<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

/**
 * mixed is the top type, so removing a union from it is subtracting the
 * members it does not subtract already, all at once. Peeling them one at a
 * time would ask each member about the mixed the earlier members left, which
 * walks everything they subtracted.
 *
 * @internal
 */
final class MixedUnionRemoveRule implements UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): ?Type
	{
		if (!$fromType instanceof MixedType || $fromType instanceof TemplateType) {
			return null;
		}

		$subtractedType = $fromType->getSubtractedType();
		$remainingTypes = [];
		foreach ($typeToRemove->getTypes() as $unionTypeToRemove) {
			if ($subtractedType !== null && $subtractedType->isSuperTypeOf($unionTypeToRemove)->yes()) {
				continue;
			}
			$remainingTypes[] = $unionTypeToRemove;
		}

		if ($remainingTypes === []) {
			return $fromType;
		}

		return $fromType->subtract(count($remainingTypes) === 1 ? $remainingTypes[0] : new UnionType($remainingTypes));
	}

}
