<?php // lint >= 8.1

namespace Bug13782;

use BackedEnum;
use function PHPStan\Testing\assertType;

enum IntEnum : int
{
	case A = 1;
	case B = 2;
}

class EnumMethods
{
	/**
	 * @template TEnum of BackedEnum
	 * @param TEnum $enum
	 * @return value-of<TEnum>
	 */
	public static function getValue(BackedEnum $enum): int|string
	{
		return $enum->value;
	}

	/**
	 * @template TEnum of BackedEnum
	 * @param TEnum|null $enum
	 * @return ($enum is TEnum ? value-of<TEnum> : null)
	 */
	public static function getNullableValue(?BackedEnum $enum): int|string|null
	{
		return $enum === null ? null : $enum->value;
	}
}

assertType("2", EnumMethods::getValue(IntEnum::B));
assertType("2", EnumMethods::getNullableValue(IntEnum::B));
