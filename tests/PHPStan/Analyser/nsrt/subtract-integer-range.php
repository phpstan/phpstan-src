<?php

namespace SubtractIntegerRange;

use function PHPStan\Testing\assertType;

/**
 * @param int<min, -1> $negative
 * @param int<min, 3> $upToThree
 * @param int<1, max> $positive
 * @param int<-5, 5> $range
 */
function doFoo($negative, $upToThree, $positive, $range): void
{
	// [a, b] - [c, d] is [a - d, b - c]
	assertType('int<1, max>', 0 - $negative);
	assertType('int<1, max>', null - $negative);
	assertType('int<2, max>', 5 - $upToThree);
	assertType('int<min, 4>', 5 - $positive);
	assertType('int<-8, max>', $range - $upToThree);
	assertType('int<min, 4>', $range - $positive);
	assertType('int<-10, 10>', $range - $range);
}
