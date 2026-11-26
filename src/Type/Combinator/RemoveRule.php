<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;

/**
 * One way TypeCombinator::remove() can answer: the type without the other
 * one, or null when the rule does not apply. remove() asks its rules in
 * order and takes the first answer.
 *
 * @internal
 */
interface RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type;

}
