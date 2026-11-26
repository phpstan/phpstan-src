<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

/**
 * Nothing is left of a type the removed one holds, and a type the removed one
 * shares no value with stays as it is.
 *
 * @internal
 */
final class SubsumptionRemoveRule implements RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type
	{
		$isSuperType = $typeToRemove->isSuperTypeOf($fromType);
		if ($isSuperType->yes()) {
			return new NeverType();
		}
		if ($isSuperType->no()) {
			return $fromType;
		}

		return null;
	}

}
