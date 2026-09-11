<?php

namespace Bug15165ImpossibleCheck;

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
