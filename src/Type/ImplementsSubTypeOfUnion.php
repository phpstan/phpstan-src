<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\TrinaryLogic;

/**
 * Indictates that isSubTypeOf may be called with a UnionType without risking
 * infinite recursion.
 */
interface ImplementsSubTypeOfUnion
{

	public function isSubTypeOf(Type $type): TrinaryLogic;

}
