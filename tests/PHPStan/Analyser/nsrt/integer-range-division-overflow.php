<?php declare(strict_types = 1);

namespace IntegerRangeDivisionOverflow;

use function PHPStan\Testing\assertType;

/**
 * @param int<-9223372036854775808, -1> $negative
 * @param int<-9223372036854775808, 9223372036854775807> $any
 */
function test(int $negative, int $any): void
{
	// PHP_INT_MIN / -1 overflows to a float, the rest of the range stays int
	assertType('int<1, 9223372036854775807>', $negative / -1);
	assertType('int<-9223372036854775807, 9223372036854775807>', $any / -1);
}
