<?php

namespace Abs64bit;

use function PHPStan\Testing\assertType;

// abs() of the smallest integer overflows and returns a float.
assertType('9.223372036854776E+18', abs(-9223372036854775807 - 1));
assertType('2147483648|9.223372036854776E+18', abs(PHP_INT_MIN));

// One step away from the overflow, so still an integer.
assertType('9223372036854775807', abs(-9223372036854775807));
assertType('2147483647|9223372036854775807', abs(-PHP_INT_MAX));

function integerRanges(int $int): void
{
	/** @var int<-9223372036854775808, 0> $int */
	assertType('int<0, max>', abs($int));

	/** @var int<-9223372036854775808, -1> $int */
	assertType('int<1, max>', abs($int));

	/** @var int<-9223372036854775808, 9223372036854775807> $int */
	assertType('int<0, max>', abs($int));

	// IntegerRangeType::fromInterval() collapses these to a single value.
	/** @var int<min, -9223372036854775808> $int */
	assertType('-9223372036854775808', $int);
	assertType('9.223372036854776E+18', abs($int));

	/** @var int<9223372036854775807, max> $int */
	assertType('9223372036854775807', $int);
	assertType('9223372036854775807', abs($int));
}
