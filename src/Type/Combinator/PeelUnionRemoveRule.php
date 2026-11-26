<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

/**
 * The members of the removed union come off one at a time, which keeps the
 * result precise: removing one can narrow the type so that removing the next
 * achieves more.
 *
 * @internal
 */
final class PeelUnionRemoveRule implements UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): Type
	{
		foreach ($typeToRemove->getTypes() as $member) {
			$fromType = TypeCombinator::remove($fromType, $member);
			if ($fromType instanceof NeverType) {
				// there is nothing left to remove from
				break;
			}
		}

		return $fromType;
	}

}
