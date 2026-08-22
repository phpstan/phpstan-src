<?php declare(strict_types = 1);

namespace IntegerRangeFloatBounds;

use function PHPStan\Testing\assertType;

// 9223372036854775808 does not fit into an int, so it is a float literal.
// It is the same float as (float) PHP_INT_MAX, which rounds up to 2 ** 63,
// and as the abs()/unary minus overflow of PHP_INT_MIN.
function aboveIntegerRange(int $i): void
{
	assertType('9.223372036854776E+18', 9223372036854775808);

	if ($i < 9223372036854775808) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}

	if ($i <= 9223372036854775808) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}

	if ($i > 9223372036854775808) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}

	if ($i >= 9223372036854775808) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}
}

// (float) PHP_INT_MIN is exactly PHP_INT_MIN, so nothing is smaller than it.
function belowIntegerRange(int $i): void
{
	assertType('-9.223372036854776E+18', -9223372036854775808);

	if ($i < -9223372036854775808) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int', $i);
	}

	if ($i >= -9223372036854775808) {
		assertType('int', $i);
	} else {
		assertType('*NEVER*', $i);
	}
}

// The biggest float below 2 ** 63 still fits into the integer range.
function insideIntegerRange(int $i): void
{
	if ($i < 9223372036854774784.0) {
		assertType('int<min, 9223372036854774783>', $i);
	}

	if ($i >= 9223372036854774784.0) {
		assertType('int<9223372036854774784, max>', $i);
	}
}
