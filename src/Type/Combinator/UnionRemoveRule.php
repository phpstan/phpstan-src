<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * One way TypeCombinator::remove() can remove a union: the type without it, or
 * null when the rule does not apply. remove() asks these rules in order before
 * any RemoveRule, and takes the first answer.
 *
 * @internal
 */
interface UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): ?Type;

}
