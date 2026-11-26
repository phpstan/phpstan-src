<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Generic\TemplateType;
use function count;
use function min;

/**
 * The unions of two shapes as one shape, and the intersections of an array
 * with an accessory type that the array takes in whole: the tryUnion() of
 * ConstantArrayType, and the tryIntersect() of ConstantArrayType and
 * OversizedArrayType.
 *
 * The intersections apply where neither side holds the other - where one does,
 * intersect() keeps the subtype as it is.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'arrayAlgebra')]
final class ArrayAlgebra
{

	/** The most optional keys whose sets tryUnionConstantArrayBesideEmptyArray() counts in an integer. */
	private const KEY_SETS_COUNTED_LIMIT = 62;

	/** @see Type::tryIntersect() */
	public static function tryIntersectConstantArray(ConstantArrayType $array, Type $otherType): ?Type
	{
		if (
			!$otherType instanceof HasOffsetType
			&& !$otherType instanceof HasOffsetValueType
			&& !$otherType instanceof AccessoryArrayListType
			&& !$otherType instanceof NonEmptyArrayType
		) {
			return null;
		}
		if (!self::overlapsWithoutContaining($array, $otherType)) {
			return null;
		}

		if ($otherType instanceof HasOffsetType) {
			return $array->makeOffsetRequired($otherType->getOffsetType());
		}

		if ($otherType instanceof AccessoryArrayListType) {
			return $array->makeList();
		}

		if ($otherType instanceof NonEmptyArrayType) {
			// the one key that can make it non-empty
			if (
				(count($array->getKeyTypes()) === 1 || $array->isList()->yes())
				&& $array->isOptionalKey(0)
				&& !$array->isUnsealed()->yes()
			) {
				return $array->makeOffsetRequired($array->getKeyTypes()[0]);
			}

			return null;
		}

		$offsetType = $otherType->getOffsetType();
		$valueType = TypeCombinator::intersect($array->getOffsetValueType($offsetType), $otherType->getValueType());
		if ($valueType instanceof NeverType) {
			return $valueType;
		}

		return $array->setOffsetValueType($offsetType, $valueType);
	}

	/** @see Type::tryIntersect() */
	public static function tryIntersectOversizedArray(OversizedArrayType $array, Type $otherType): ?Type
	{
		// an oversized array no longer tracks its offsets
		if (!$otherType instanceof HasOffsetValueType || !self::overlapsWithoutContaining($array, $otherType)) {
			return null;
		}

		return $array;
	}

	/**
	 * The union of two shapes as one shape when that loses nothing: when one
	 * holds the other, or when they differ at a single key only - in its value,
	 * whether it is optional, or whether it is there at all - where the merged
	 * shape has the union of the values, optional unless both require it.
	 * Shapes differing at more keys stay a tagged union.
	 *
	 * An empty array merges only into a shape that already holds it:
	 * `array{}|array{a: int}` keeps it apart. Operations on a shape, such as
	 * appending to a list, are exact on each member of a union but not on
	 * optional keys, and `$a = []; if (...) { $a[] = 1; }` is the most common
	 * union of shapes.
	 *
	 * Shapes with unsealed extras are left to TypeCombinator.
	 *
	 * @see Type::tryUnion()
	 */
	public static function tryUnionConstantArray(ConstantArrayType $array, Type $otherType): ?Type
	{
		// a bare ConstantArrayType is the only type whose getConstantArrays() is itself
		$otherShapes = $otherType->getConstantArrays();
		if ($otherShapes !== [$otherType]) {
			return null;
		}
		$otherType = $otherShapes[0];
		if (
			$array instanceof TemplateType
			|| $otherType instanceof TemplateType
			|| $array->isUnsealed()->yes()
			|| $otherType->isUnsealed()->yes()
		) {
			return null;
		}

		// $bigger has every key of $smaller, or they cannot merge
		[$bigger, $smaller] = count($array->getKeyTypes()) >= count($otherType->getKeyTypes())
			? [$array, $otherType]
			: [$otherType, $array];
		$smallerKeys = [];
		foreach ($smaller->getKeyTypes() as $j => $keyType) {
			$smallerKeys[$keyType->getValue()] = $j;
		}

		$smallerValueTypes = $smaller->getValueTypes();
		$biggerValueTypes = $bigger->getValueTypes();
		$matchedKeys = 0;
		$differingKeys = 0;
		foreach ($bigger->getKeyTypes() as $i => $keyType) {
			$j = $smallerKeys[$keyType->getValue()] ?? null;
			if ($j === null) {
				$differingKeys++;
				continue;
			}
			$matchedKeys++;
			if (
				$bigger->isOptionalKey($i) === $smaller->isOptionalKey($j)
				&& $biggerValueTypes[$i]->equals($smallerValueTypes[$j])
			) {
				continue;
			}
			$differingKeys++;
		}
		if ($matchedKeys !== count($smallerKeys)) {
			return null;
		}

		if ($differingKeys === 0) {
			return $bigger;
		}

		if ($differingKeys === 1 && $smallerKeys !== []) {
			return $bigger->mergeWith($smaller);
		}

		if (self::areListsOfOneLengthRange($bigger, $smaller)) {
			return $bigger->mergeWith($smaller);
		}

		if ($bigger->isSuperTypeOf($smaller)->yes()) {
			return $bigger;
		}
		if ($matchedKeys === count($bigger->getKeyTypes()) && $smaller->isSuperTypeOf($bigger)->yes()) {
			return $smaller;
		}

		return null;
	}

	/**
	 * The union of two shapes as one shape in a union that holds the empty
	 * array as well: when the merged shape holds the two and at most the empty
	 * array besides, such as `array{a: 1}|array{a?: 1, b: 2}` beside
	 * `array{}`, which together are `array{a?: 1, b?: 2}`.
	 *
	 * The shapes are not empty and not lists - a list holds the lists of its lengths, which
	 * tryUnionConstantArray() merges - and have the same value at each key
	 * they share, so the shapes they hold are the sets of keys from their
	 * required keys to all their keys, and counting those sets tells whether
	 * the merged shape holds any other.
	 */
	public static function tryUnionConstantArrayBesideEmptyArray(ConstantArrayType $array, ConstantArrayType $otherType): ?ConstantArrayType
	{
		if (
			$array instanceof TemplateType
			|| $otherType instanceof TemplateType
			|| $array->isUnsealed()->yes()
			|| $otherType->isUnsealed()->yes()
			|| $array->isList()->yes()
			|| $otherType->isList()->yes()
			// the empty array itself stays apart, see tryUnionConstantArray()
			|| $array->getKeyTypes() === []
			|| $otherType->getKeyTypes() === []
		) {
			return null;
		}

		[$bigger, $smaller] = count($array->getKeyTypes()) >= count($otherType->getKeyTypes())
			? [$array, $otherType]
			: [$otherType, $array];
		$biggerKeys = [];
		foreach ($bigger->getKeyTypes() as $i => $keyType) {
			$biggerKeys[$keyType->getValue()] = $i;
		}

		$biggerValueTypes = $bigger->getValueTypes();
		$smallerValueTypes = $smaller->getValueTypes();
		$biggerRequired = count($biggerKeys) - count($bigger->getOptionalKeys());
		$smallerRequired = 0;
		// the keys both require, and the keys either requires
		$bothRequire = 0;
		$eitherRequires = $biggerRequired;
		$biggerRequiredInSmaller = 0;
		foreach ($smaller->getKeyTypes() as $j => $keyType) {
			$i = $biggerKeys[$keyType->getValue()] ?? null;
			if ($i === null || !$biggerValueTypes[$i]->equals($smallerValueTypes[$j])) {
				return null;
			}
			if (!$bigger->isOptionalKey($i)) {
				$biggerRequiredInSmaller++;
			}
			if ($smaller->isOptionalKey($j)) {
				continue;
			}
			$smallerRequired++;
			if ($bigger->isOptionalKey($i)) {
				$eitherRequires++;
			} else {
				$bothRequire++;
			}
		}
		// the sets of keys of the merged shape and those of both, without the empty one
		$biggerCount = count($biggerKeys);
		$smallerCount = count($smaller->getKeyTypes());
		if ($biggerCount - $bothRequire > self::KEY_SETS_COUNTED_LIMIT) {
			return null;
		}
		$merged = (1 << $biggerCount - $bothRequire) - ($bothRequire === 0 ? 1 : 0);
		// the sets of keys both hold: from the keys either requires to the
		// keys of the smaller one, when it has all the keys the bigger requires
		$shared = $biggerRequiredInSmaller === $biggerRequired ? 1 << $smallerCount - $eitherRequires : 0;
		$union = (1 << $biggerCount - $biggerRequired)
			+ (1 << $smallerCount - $smallerRequired)
			- $shared
			- ($biggerRequired === 0 || $smallerRequired === 0 ? 1 : 0);
		if ($merged !== $union) {
			return null;
		}

		return $bigger->mergeWith($smaller);
	}

	/**
	 * Whether two lists are one list of the lengths of both: a list shape holds
	 * the lists of each length from its required keys to all its keys, the
	 * value at each position being its value type there. The merged list,
	 * with the union of the values at each position, holds exactly the two when
	 * at each length it holds, the lists of that length the two hold are one
	 * product of values: one list's, or both when one holds the other's or
	 * they differ at one position below that length only.
	 *
	 * $bigger has every key of $smaller.
	 */
	private static function areListsOfOneLengthRange(ConstantArrayType $bigger, ConstantArrayType $smaller): bool
	{
		if (!$bigger->isList()->yes() || !$smaller->isList()->yes()) {
			return false;
		}

		$biggerMax = count($bigger->getKeyTypes());
		$smallerMax = count($smaller->getKeyTypes());
		$biggerMin = $biggerMax - count($bigger->getOptionalKeys());
		$smallerMin = $smallerMax - count($smaller->getOptionalKeys());
		if ($smallerMax === 0 || $smallerMax < $biggerMin - 1) {
			// the empty array stays apart, see tryUnionConstantArray(); or a
			// length of neither between them
			return false;
		}

		// a list's keys are its positions in order
		$biggerValueTypes = $bigger->getValueTypes();
		$smallerValueTypes = $smaller->getValueTypes();
		$minLength = min($biggerMin, $smallerMin);
		// over the positions below the length
		$differingPositions = 0;
		$biggerHoldsSmaller = true;
		$smallerHoldsBigger = true;
		for ($length = 1; $length <= $biggerMax; $length++) {
			$i = $length - 1;
			if ($i < $smallerMax && !$biggerValueTypes[$i]->equals($smallerValueTypes[$i])) {
				$differingPositions++;
				$biggerHoldsSmaller = $biggerHoldsSmaller && $biggerValueTypes[$i]->isSuperTypeOf($smallerValueTypes[$i])->yes();
				$smallerHoldsBigger = $smallerHoldsBigger && $smallerValueTypes[$i]->isSuperTypeOf($biggerValueTypes[$i])->yes();
			}
			if ($length < $minLength) {
				continue;
			}

			$inBigger = $length >= $biggerMin;
			$inSmaller = $length >= $smallerMin && $length <= $smallerMax;
			if ($inBigger && $inSmaller) {
				if ($differingPositions > 1 && !$biggerHoldsSmaller && !$smallerHoldsBigger) {
					return false;
				}
			} elseif ($inBigger) {
				if (!$biggerHoldsSmaller) {
					return false;
				}
			} elseif (!$smallerHoldsBigger) {
				return false;
			}
		}

		return true;
	}

	private static function overlapsWithoutContaining(Type $array, Type $otherType): bool
	{
		return !$otherType->isSuperTypeOf($array)->yes() && $array->isSuperTypeOf($otherType)->maybe();
	}

}
