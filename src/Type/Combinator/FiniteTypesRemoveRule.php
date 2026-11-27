<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

/**
 * A type of finitely many values, such as bool or an enum, without the finite
 * values of the removed type is the rest of its values.
 *
 * @internal
 */
final class FiniteTypesRemoveRule implements RemoveRule
{

	public function tryRemove(Type $fromType, Type $typeToRemove): ?Type
	{
		$fromFiniteTypes = $fromType->getFiniteTypes();
		if ($fromFiniteTypes === []) {
			return null;
		}
		$finiteTypesToRemove = $typeToRemove->getFiniteTypes();
		if ($finiteTypesToRemove === []) {
			return null;
		}

		$result = [];
		foreach ($fromFiniteTypes as $finiteType) {
			foreach ($finiteTypesToRemove as $finiteTypeToRemove) {
				if ($finiteType->equals($finiteTypeToRemove)) {
					continue 2;
				}
			}

			$result[] = $finiteType;
		}

		if (count($result) === count($fromFiniteTypes)) {
			return $fromType;
		}
		if ($result === []) {
			return new NeverType();
		}
		if (count($result) === 1) {
			return $result[0];
		}

		return new UnionType($result);
	}

}
