<?php declare(strict_types = 1);

namespace Bug15243;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, max> $a
 * @param int<10, 20> $range
 * @param int<1, 5>|int<10, 20> $ranges
 * @param int<30, max>|7 $unboundedOrSeven
 * @param int<-20, -10>|int<10, 15> $mixedSign
 * @param int<-20, -10>|int<-5, -3> $negative
 */
function foo(int $a, int $b, int $range, int $ranges, int $unboundedOrSeven, int $mixedSign, int $negative): void
{
	assertType('int<0, 19>', $a % $range);
	assertType('int<0, 19>', $a % $ranges);
	assertType('int<-19, 19>', $b % $ranges);
	assertType('int<0, max>', $a % $unboundedOrSeven);
	assertType('int<0, 19>', $a % $mixedSign);
	assertType('int<0, 19>', $a % $negative);
}

/**
 * @param int<30, max>|7 $divisor
 */
function bar(int $divisor): bool
{
	return 100 % $divisor > 10; // e.g. 100 % 60 === 40
}

/**
 * @param int<0, max> $a
 * @param int<min, 0> $nonPositive
 * @param int<1, 5>|int<10, 20> $ranges
 * @param int<min, 20> $unboundedMin
 */
function analogous(int $a, int $nonPositive, int $ranges, int $unboundedMin): void
{
	assertType('int<min, 0>', $nonPositive % $a);
	assertType('int<-19, 0>', $nonPositive % $ranges);
	assertType('int<0, max>', $a % $unboundedMin);

	$c = $a;
	$c %= $ranges;
	assertType('int<0, 19>', $c);
}
