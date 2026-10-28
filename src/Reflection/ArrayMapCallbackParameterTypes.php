<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function is_int;
use function max;

/**
 * With more than one array, array_map() calls the callback once per index of
 * the longest array, and passes null for each array that is shorter.
 */
#[ReferencedByTurboExtension(key: 'arrayMapCallbackParameterTypes')]
final class ArrayMapCallbackParameterTypes
{

	/**
	 * Adds null to the value type of each array that is shorter than another one
	 * when both have their largest size. Only when the size of every array is known.
	 *
	 * The largest sizes are compared, not the smallest, because two arrays can be
	 * one array: array_map($cb, $a, $a) never passes null, whatever sizes $a can have.
	 *
	 * @param list<Type> $valueTypes
	 * @param list<Type> $arrayTypes the array each value type comes from
	 * @return list<Type>
	 */
	public static function addPaddingNull(array $valueTypes, array $arrayTypes): array
	{
		if (count($arrayTypes) < 2) {
			return $valueTypes;
		}

		$maxSizes = [];
		foreach ($arrayTypes as $i => $arrayType) {
			$sizes = [];
			foreach ($arrayType->getArraySize()->getFiniteTypes() as $sizeType) {
				foreach ($sizeType->getConstantScalarValues() as $size) {
					if (!is_int($size)) {
						continue;
					}

					$sizes[] = $size;
				}
			}

			if ($sizes === []) {
				// general arrays can have the same size, but the type system cannot say so
				return $valueTypes;
			}

			$maxSizes[$i] = max($sizes);
		}

		$longestSize = max($maxSizes);
		foreach ($valueTypes as $i => $valueType) {
			if ($maxSizes[$i] === $longestSize) {
				continue;
			}

			$valueTypes[$i] = TypeCombinator::addNull($valueType);
		}

		return $valueTypes;
	}

}
