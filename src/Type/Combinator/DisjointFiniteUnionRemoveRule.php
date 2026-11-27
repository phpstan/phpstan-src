<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\FiniteTypeSet;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/**
 * A value none of the removed union's members stands for overlaps none of
 * them: it stays as it is, found with one lookup instead of a removal per
 * member.
 *
 * @internal
 */
final class DisjointFiniteUnionRemoveRule implements UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): ?Type
	{
		$fromTypeKey = FiniteTypeSet::key($fromType);
		if ($fromTypeKey === null) {
			return null;
		}

		$finiteTypeSet = $typeToRemove->getFiniteTypeSet();
		if ($finiteTypeSet === null || !$finiteTypeSet->isComplete() || $finiteTypeSet->has($fromTypeKey)) {
			return null;
		}

		return $fromType;
	}

}
