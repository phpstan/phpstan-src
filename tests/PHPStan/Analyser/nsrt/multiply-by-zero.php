<?php // lint >= 8.0

namespace MultiplyByZero;

use function PHPStan\Testing\assertType;

/**
 * @param numeric-string $numericString
 */
function doFoo(int $int, float $float, int|float $intOrFloat, string $numericString, bool $bool): void
{
	// 0 * 1.5 is 0.0, 0 * -1.5 is -0.0 and 0 * INF is NAN
	assertType('0.0|NAN', 0 * $float);
	assertType('0.0|NAN', $float * 0);
	assertType('0.0|NAN', null * $float);
	assertType('0|0.0|NAN', 0 * $intOrFloat);
	assertType('0|0.0|NAN', null * $numericString);
	assertType('0|0.0|NAN', $numericString * false);

	// an integer times zero is always the integer 0
	assertType('0', 0 * $int);
	assertType('0', $int * null);
	assertType('0', $bool * 0);
}
