<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;

/**
 * The type's own Type::tryRemove().
 *
 * @internal
 */
final class TypeRemoveRule implements RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type
	{
		return $fromType->tryRemove($typeToRemove);
	}

}
