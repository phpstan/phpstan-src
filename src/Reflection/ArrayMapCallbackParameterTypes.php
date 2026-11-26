<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_fill;
use function count;
use function in_array;
use function is_int;
use function max;
use function min;

/**
 * With more than one array, array_map() calls the callback once per index of
 * the longest array, and passes null for each array that is shorter.
 */
#[ReferencedByTurboExtension(key: 'arrayMapCallbackParameterTypes')]
final class ArrayMapCallbackParameterTypes
{

	/**
	 * Returns the value type of each array the args pass, and the value type of
	 * the arrays after them when their number is not known (see
	 * UnpackedArgumentTypes::expand()).
	 *
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked whether each arg is unpacked
	 * @return array{list<Type>, Type|null}
	 */
	public static function getCallbackValueTypes(array $argTypes, array $unpacked): array
	{
		[$arrayTypes, $restArrayType] = self::expand($argTypes, $unpacked);

		$valueTypes = [];
		foreach ($arrayTypes as $arrayType) {
			$valueTypes[] = $arrayType->getIterableValueType();
		}

		return self::addPaddingNullToArgs($valueTypes, $restArrayType?->getIterableValueType(), $argTypes, $unpacked);
	}

	/**
	 * Does what addPaddingNull() does for the value types of the arrays that
	 * UnpackedArgumentTypes::expand() gives for the args.
	 *
	 * expand() merges each position across the shapes of an unpacked union, so
	 * that each shape's arrays are compared here on their own. The arrays after
	 * the known positions are different arrays, so their smallest size counts.
	 *
	 * @param list<Type> $valueTypes
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked whether each arg is unpacked
	 * @return array{list<Type>, Type|null}
	 */
	public static function addPaddingNullToArgs(array $valueTypes, ?Type $restValueType, array $argTypes, array $unpacked): array
	{
		$knownCount = count($valueTypes);
		$padded = array_fill(0, $knownCount, false);
		$restPadded = false;
		foreach (self::getShapeVariants($argTypes, $unpacked) as $variant) {
			if ($variant === null) {
				// more than one unpacked union: add null wherever an array can be shorter
				$padded = array_fill(0, $knownCount, true);
				$restPadded = true;
				break;
			}

			[$arrayTypes, $restArrayType] = self::expand($variant, $unpacked);
			[$variantPadded, $variantRestPadded] = self::getPaddedArrays($arrayTypes, $restArrayType);
			foreach ($variantPadded as $i => $isPadded) {
				if (!$isPadded) {
					continue;
				}

				// a shape can have more known positions than the merged union
				if ($i < $knownCount) {
					$padded[$i] = true;
				} else {
					$restPadded = true;
				}
			}
			$restPadded = $restPadded || $variantRestPadded;
		}

		foreach ($valueTypes as $i => $valueType) {
			if (!$padded[$i]) {
				continue;
			}

			$valueTypes[$i] = TypeCombinator::addNull($valueType);
		}

		if ($restValueType !== null && $restPadded) {
			$restValueType = TypeCombinator::addNull($restValueType);
		}

		return [$valueTypes, $restValueType];
	}

	/**
	 * The args once for each shape of the one unpacked arg that is a union of
	 * shapes. null when more than one unpacked arg is.
	 *
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked
	 * @return list<list<Type>|null>
	 */
	private static function getShapeVariants(array $argTypes, array $unpacked): array
	{
		$unionIndex = null;
		foreach ($argTypes as $i => $argType) {
			if (!$unpacked[$i] || count($argType->getConstantArrays()) < 2) {
				continue;
			}

			if ($unionIndex !== null) {
				return [null];
			}

			$unionIndex = $i;
		}

		if ($unionIndex === null) {
			return [$argTypes];
		}

		$variants = [];
		foreach ($argTypes[$unionIndex]->getConstantArrays() as $constantArray) {
			$variant = [];
			foreach ($argTypes as $i => $argType) {
				$variant[] = $i === $unionIndex ? $constantArray : $argType;
			}
			$variants[] = $variant;
		}

		return $variants;
	}

	/**
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked
	 * @return array{list<Type>, Type|null}
	 */
	private static function expand(array $argTypes, array $unpacked): array
	{
		if (!in_array(true, $unpacked, true)) {
			return [$argTypes, null];
		}

		return UnpackedArgumentTypes::expand($argTypes, $unpacked);
	}

	/**
	 * Which of the arrays are shorter than another one, as addPaddingNull()
	 * decides it, and whether one of the arrays after them, whose number is not
	 * known, can be.
	 *
	 * @param list<Type> $arrayTypes
	 * @return array{list<bool>, bool}
	 */
	private static function getPaddedArrays(array $arrayTypes, ?Type $restArrayType): array
	{
		$notPadded = [array_fill(0, count($arrayTypes), false), false];
		if ($restArrayType === null && count($arrayTypes) < 2) {
			return $notPadded;
		}

		$maxSizes = [];
		foreach ($arrayTypes as $arrayType) {
			$sizes = self::getSizes($arrayType);
			if ($sizes === []) {
				// general arrays can have the same size, but the type system cannot say so
				return $notPadded;
			}

			$maxSizes[] = max($sizes);
		}

		$restSizes = [];
		if ($restArrayType !== null) {
			$restSizes = self::getSizes($restArrayType);
			if ($restSizes === []) {
				return $notPadded;
			}
		}

		$sizes = [...$maxSizes, ...$restSizes];
		if ($sizes === []) {
			return $notPadded;
		}

		$longestSize = max($sizes);
		$padded = [];
		foreach ($maxSizes as $maxSize) {
			$padded[] = $maxSize < $longestSize;
		}

		return [$padded, $restSizes !== [] && min($restSizes) < $longestSize];
	}

	/**
	 * @return list<int>
	 */
	private static function getSizes(Type $arrayType): array
	{
		$sizes = [];
		foreach ($arrayType->getArraySize()->getFiniteTypes() as $sizeType) {
			foreach ($sizeType->getConstantScalarValues() as $size) {
				if (!is_int($size)) {
					continue;
				}

				$sizes[] = $size;
			}
		}

		return $sizes;
	}

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
			$sizes = self::getSizes($arrayType);
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
