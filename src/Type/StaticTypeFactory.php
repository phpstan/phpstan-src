<?php declare(strict_types = 1);

namespace PHPStan\Type;

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
			$falsey = new UnionType([
				new NullType(),
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				new ConstantFloatType(0.0),
				new ConstantStringType(''),
				new ConstantStringType('0'),
				new ConstantArrayType([], []),
			]);
		}

		return $falsey;
	}

	public static function truthy(): Type
	{
		static $truthy;

		if ($truthy === null) {
			$truthy = new MixedType(false, self::falsey());
		}

		return $truthy;
	}

}
