<?php // lint >= 8.1

namespace Bug15165;

use BackedEnum;
use ReflectionEnum;
use UnitEnum;
use function PHPStan\Testing\assertType;

/**
 * @template T of UnitEnum
 * @param ReflectionEnum<T> $enum
 * @return void
 */
function testUnit(ReflectionEnum $enum): void
{
	if ($enum->isBacked()) {
		assertType('ReflectionEnum<BackedEnum&T of UnitEnum (function Bug15165\testUnit(), argument)>', $enum);
	}
}

/**
 * @template T of BackedEnum
 * @param ReflectionEnum<T> $enum
 * @return void
 */
function testBacked(ReflectionEnum $enum): void
{
	if (!$enum->isBacked()) {
		assertType('*NEVER*', $enum);
	}
}

/**
 * @template T of BackedEnum|UnitEnum
 * @param ReflectionEnum<T> $enum
 * @return void
 */
function testAny(ReflectionEnum $enum): void
{
	if ($enum->isBacked()) {
		assertType('ReflectionEnum<BackedEnum&T of UnitEnum (function Bug15165\testAny(), argument)>', $enum);
	}
}
