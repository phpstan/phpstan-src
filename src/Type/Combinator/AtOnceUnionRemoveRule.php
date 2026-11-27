<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

/**
 * A union comes off all at once where that gives what removing its members
 * one at a time would: from a sealed hierarchy - an enum, or a hierarchy an
 * AllowedSubTypesClassReflectionExtension seals - which subtracts by dropping
 * allowed subtypes in any order, and from a union, which removes the whole
 * union from each of its members and weighs this same question again for
 * each. Peeling the members off there would rebuild the subtraction once per
 * member - removing an enum from its own type would cost O(cases^2).
 *
 * @internal
 */
final class AtOnceUnionRemoveRule implements UnionRemoveRule
{

	public function tryRemove(Type $fromType, UnionType $typeToRemove): ?Type
	{
		$classReflections = $fromType->getObjectClassReflections();
		$isSealed = count($classReflections) === 1 && $classReflections[0]->getAllowedSubTypes() !== null;
		$isUnion = $fromType instanceof UnionType;
		if (!$isSealed && !$isUnion) {
			return null;
		}

		$removed = $fromType->tryRemove($typeToRemove);
		if ($removed !== null) {
			return $removed;
		}

		// a union removed the whole union from each of its members and none of
		// them changed - removing the members one at a time cannot change one
		// either, peeling would only repeat that work
		return $isUnion ? $fromType : null;
	}

}
