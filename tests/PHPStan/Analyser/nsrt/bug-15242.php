<?php declare(strict_types = 1);

namespace Bug15242;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 30> $nonNegative
 * @param int<-30, 30> $any
 * @param int<-10, -5> $negativeRange
 * @param int<-20, 5> $mixedSignRange
 */
function foo(int $nonNegative, int $any, int $negativeRange, int $mixedSignRange): void
{
	assertType('int<0, 8>', $nonNegative % 9);
	assertType('int<0, 8>', $nonNegative % -9);
	assertType('int<-8, 8>', $any % -9);
	assertType('int<0, 9>', $nonNegative % $negativeRange);
	assertType('int<0, 19>', $nonNegative % $mixedSignRange);
}

/**
 * @param int<0, 10> $i
 */
function phpIntMinDivisor(int $i): void
{
	assertType('int<0, 10>', $i % (-9223372036854775807 - 1));
	assertType('int<0, 10>', $i % PHP_INT_MIN);
}
