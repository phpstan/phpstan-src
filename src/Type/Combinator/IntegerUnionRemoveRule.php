<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\NeverType;
use PHPStan\Type\ScalarUnion;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function get_class;

/**
 * Integers, integer ranges and constants and their plain unions are what is
 * left of their intervals without the removed ones, swept in the order of
 * their bounds instead of removed one member at a time.
 *
 * @internal
 */
final class IntegerUnionRemoveRule implements UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): ?Type
	{
		if (get_class($typeToRemove) !== UnionType::class) {
			return null;
		}

		$members = $fromType instanceof UnionType && get_class($fromType) === UnionType::class ? $fromType->getTypes() : [$fromType];
		$integers = ScalarUnion::removeIntegers($members, $typeToRemove->getTypes());
		if ($integers === null) {
			return null;
		}
		if ($integers === []) {
			return new NeverType();
		}

		return TypeCombinator::union(...$integers);
	}

}
