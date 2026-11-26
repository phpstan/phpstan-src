<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * Removing `mixed~T` leaves only what is also T.
 *
 * @internal
 */
final class SubtractedMixedRemoveRule implements RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type
	{
		if (!$typeToRemove instanceof MixedType) {
			return null;
		}
		$subtractedType = $typeToRemove->getSubtractedType();
		if ($subtractedType === null) {
			return null;
		}

		return TypeCombinator::intersect($fromType, $subtractedType);
	}

}
