<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;

final class StaticTypeFactory
{

	public static function falsey(): Type
	{
		static $falsey;

		if ($falsey === null) {
			$falsey = TypeCombinator::union(
				new NullType(),
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				new ConstantFloatType(0.0),
				new ConstantStringType(''),
				new ConstantStringType('0'),
				new ConstantArrayType([], []),
			);
		}

		return $falsey;
	}

	public static function truthy(): Type
	{
		static $truthy;

		if ($truthy === null) {
			$truthy = new MixedType(subtractedType: self::falsey());
		}

		return $truthy;
	}

	public static function generalOffsetValueType(): Type
	{
		static $generalOffsetValueType;

		if ($generalOffsetValueType === null) {
			$generalOffsetValueType = TypeCombinator::union(
				new ArrayType(new MixedType(), new MixedType()),
				new ObjectType(ArrayAccess::class),
				new NullType(),
			);
		}

		return $generalOffsetValueType;
	}

	public static function intOffsetValueType(): Type
	{
		static $intOffsetValueType;

		if ($intOffsetValueType === null) {
			$intOffsetValueType = TypeCombinator::union(
				self::generalOffsetValueType(),
				new StringType(),
			);
		}

		return $intOffsetValueType;
	}

}
