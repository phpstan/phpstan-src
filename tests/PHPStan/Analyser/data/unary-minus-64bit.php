<?php

namespace UnaryMinus64bit;

use function PHPStan\Testing\assertType;

// Negating the smallest integer overflows and produces a float.
assertType('9.223372036854776E+18', -(-9223372036854775807 - 1));
assertType('2147483648|9.223372036854776E+18', -PHP_INT_MIN);

// Negating the largest integer stays an integer.
assertType('-9223372036854775807|-2147483647', -PHP_INT_MAX);

$min = -9223372036854775807 - 1;
assertType('9.223372036854776E+18', -$min);

assertType('9223372036854775807', -(-9223372036854775807));
assertType('-9223372036854775807', -9223372036854775807);

function integerRanges(int $int): void
{
	/** @var int<min, -1> $int */
	assertType('int<1, max>', -$int);

	/** @var int<-9223372036854775808, -1> $int */
	assertType('int<1, max>', -$int);
}

function constantUnion(int $int): void
{
	/** @var -1|-2 $int */
	assertType('1|2', -$int);

	/** @var 9223372036854775807|25 $int */
	assertType('-9223372036854775807|-25', -$int);
}

// The union has one member that overflows and one that does not.
function partiallyOverflowingUnion(bool $bool): void
{
	$int = $bool ? -9223372036854775807 - 1 : 25;
	assertType('-9223372036854775808|25', $int);
	assertType('-25|9.223372036854776E+18', -$int);
}
