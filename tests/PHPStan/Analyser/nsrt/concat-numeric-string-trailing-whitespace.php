<?php // lint >= 8.0

namespace ConcatNumericStringTrailingWhitespace;

use function PHPStan\Testing\assertType;

/**
 * @param numeric-string $numericString
 * @param decimal-int-string $decimalIntString
 * @param int<0, max> $nonNegative
 */
function doFoo(string $numericString, string $decimalIntString, int $int, float $float, $nonNegative): void
{
	// PHP 8 allows trailing whitespace in numeric strings: '1 ' . '5' is not numeric
	assertType('non-falsy-string', $numericString . '5');
	assertType('non-falsy-string', $numericString . $nonNegative);
	assertType('lowercase-string&non-falsy-string&uppercase-string', '1 ' . $nonNegative);

	// integers, floats and decimal-int-strings never end in whitespace
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $decimalIntString . '5');
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $int . $nonNegative);
	assertType('non-falsy-string&uppercase-string', $float . $nonNegative); // not numeric because of INF and NAN
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', '15' . $nonNegative);
}
