<?php // lint >= 8.0

namespace MultiplyByZero;

use function PHPStan\Testing\assertType;

/**
 * @param numeric-string $numericString
 */
function doFoo(int $int, float $float, int|float $intOrFloat, string $numericString, bool $bool): void
{
	// 0 * 1.5 is 0.0, 0 * -1.5 is -0.0 and 0 * INF is NAN
	assertType('float', 0 * $float);
	assertType('float', $float * 0);
	assertType('float', null * $float);
	assertType('float|int', 0 * $intOrFloat);
	assertType('float|int', null * $numericString);
	assertType('float|int', $numericString * false);

	// an integer times zero is always the integer 0
	assertType('0', 0 * $int);
	assertType('0', $int * null);
	assertType('0', $bool * 0);
}
