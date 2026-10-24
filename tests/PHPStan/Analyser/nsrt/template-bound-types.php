<?php declare(strict_types = 1);

namespace TemplateBoundTypes;

use Exception;
use InvalidArgumentException;
use function PHPStan\Testing\assertType;

/**
 * @template T of class-string
 * @param T $class
 * @return T
 */
function classStringBound(string $class): string
{
	return $class;
}

/**
 * @template T of class-string<Exception>
 * @param T $class
 * @return T
 */
function genericClassStringBound(string $class): string
{
	return $class;
}

/**
 * @template T of int<0, 10>
 * @param T $i
 * @return T
 */
function integerRangeBound(int $i): int
{
	return $i;
}

/**
 * @template T of 1.5
 * @param T $f
 * @return T
 */
function constantFloatBound(float $f): float
{
	return $f;
}

function test(): void
{
	assertType("'Exception'", classStringBound(Exception::class));
	assertType("'InvalidArgumentException'", genericClassStringBound(InvalidArgumentException::class));
	assertType('5', integerRangeBound(5));
	assertType('1.5', constantFloatBound(1.5));
}
