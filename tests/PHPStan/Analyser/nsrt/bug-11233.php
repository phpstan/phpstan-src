<?php // lint >= 8.1

namespace Bug11233;

use function PHPStan\Testing\assertType;

class EnumExtension
{
	/**
	 * @template T of \UnitEnum
	 *
	 * @param class-string<T> $enum
	 */
	public static function getEnumCases(string $enum): void
	{
		assertType('list<T of UnitEnum (method Bug11233\EnumExtension::getEnumCases(), argument)>', $enum::cases());
	}

	/**
	 * @template T of \BackedEnum
	 *
	 * @param class-string<T> $enum
	 *
	 * @return list<T>
	 */
	public static function getEnumCases2(string $enum): void
	{
		assertType('list<T of BackedEnum (method Bug11233\EnumExtension::getEnumCases2(), argument)>', $enum::cases());
	}
}
