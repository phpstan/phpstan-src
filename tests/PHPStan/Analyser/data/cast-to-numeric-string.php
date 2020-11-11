<?php

namespace CastToNumericString;

use function PHPStan\Analyser\assertType;

/**
 * @param int|float|numeric-string $numeric
 * @param numeric $numeric2
 * @param number $number
 * @param positive-int $positive
 * @param negative-int $negative
 * @param 1 $constantInt
 */
function foo(int $a, float $b, $numeric, $numeric2, $number, $positive, $negative, $constantInt): void {
	assertType('string&numeric', (string)$a);
	assertType('string&numeric', (string)$b);
	assertType('string&numeric', (string)$numeric);
	assertType('string&numeric', (string)$numeric2);
	assertType('string&numeric', (string)$number);
	assertType('string&numeric', (string)$positive);
	assertType('string&numeric', (string)$negative);
	assertType("'1'", (string)$constantInt);
}
