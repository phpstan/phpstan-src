<?php // lint >= 8.0

namespace Bug7096;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param class-string<\BackedEnum> $enumClass
	 */
	function enumFromString(string $enumClass, string|int $value): void
	{
		assertType(\BackedEnum::class, $enumClass::from($value));
		assertType(\BackedEnum::class . '|null', $enumClass::tryFrom($value));
	}

	function customStaticMethod(): static
	{
		return new static();
	}

	/**
	 * @param class-string<self> $class
	 */
	function test(string $class): void
	{
		assertType(self::class, $class::customStaticMethod());
	}

}
