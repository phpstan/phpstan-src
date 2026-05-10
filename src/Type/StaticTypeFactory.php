<?php declare(strict_types = 1);

namespace PHPStan\Type;

use ArrayAccess;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
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

	public static function argv(): Type
	{
		return new IntersectionType([
			new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), new StringType()),
			new NonEmptyArrayType(),
			new AccessoryArrayListType(),
		]);
	}

	public static function argc(): Type
	{
		return IntegerRangeType::fromInterval(1, null);
	}

	public static function generalOffsetAccessibleType(): Type
	{
		static $generalOffsetAccessible;

		if ($generalOffsetAccessible === null) {
			$generalOffsetAccessible = TypeCombinator::union(
				new ArrayType(new MixedType(), new MixedType()),
				new ObjectType(ArrayAccess::class),
				new NullType(),
			);
		}

		return $generalOffsetAccessible;
	}

	public static function intOffsetAccessibleType(): Type
	{
		static $intOffsetAccessible;

		if ($intOffsetAccessible === null) {
			$intOffsetAccessible = TypeCombinator::union(
				self::generalOffsetAccessibleType(),
				new StringType(),
			);
		}

		return $intOffsetAccessible;
	}

}
