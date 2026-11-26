<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use function count;

/**
 * The intersections of an array with an accessory type that the array takes in
 * whole: the tryIntersect() of ConstantArrayType and OversizedArrayType.
 *
 * They apply where neither side holds the other - where one does, intersect()
 * keeps the subtype as it is.
 *
 * @internal
 */
#[ReferencedByTurboExtension(key: 'arrayAlgebra')]
final class ArrayAlgebra
{

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

	private static function overlapsWithoutContaining(Type $array, Type $otherType): bool
	{
		return !$otherType->isSuperTypeOf($array)->yes() && $array->isSuperTypeOf($otherType)->maybe();
	}

}
