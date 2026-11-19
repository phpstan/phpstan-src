<?php // lint >= 8.1

namespace Bug15165;

use BackedEnum;
use ReflectionEnum;
use UnitEnum;

/**
 * @template T of UnitEnum
 * @param ReflectionEnum<T> $enum
 * @return void
 */
function testUnit(ReflectionEnum $enum): void
{
	if ($enum->isBacked()) {
		echo "Backed!";
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
		echo "Unit!";
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
		echo "Backed!";
	}
}

enum UnitFoo
{
	case A;
}

enum BackedFoo: int
{
	case A = 1;
}

function testConcrete(): void
{
	$unit = new ReflectionEnum(UnitFoo::class);
	if ($unit->isBacked()) {
		echo "Backed!";
	}

	$backed = new ReflectionEnum(BackedFoo::class);
	if ($backed->isBacked()) {
		echo "Backed!";
	}
}
