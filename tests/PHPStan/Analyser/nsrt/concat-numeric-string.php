<?php

namespace ConcatNumericString;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, max> $nonNegative
 * @param numeric-string $numericString
 */
function doFoo(int $int, $nonNegative, string $numericString): void
{
	// an integer followed by digits stays numeric
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $int . '5');
	assertType('lowercase-string&non-falsy-string&numeric-string&uppercase-string', $int . $nonNegative);

	// '1 1' and '1+5' are not numeric, although ' 1' and '+5' are
	assertType('lowercase-string&non-falsy-string&uppercase-string', $int . ' 1');
	assertType('lowercase-string&non-falsy-string&uppercase-string', $int . '+5');

	// PHP 7 does not allow trailing whitespace in numeric strings
	assertType('lowercase-string&non-falsy-string&uppercase-string', $int . "5\n");
	assertType('non-falsy-string', $numericString . "5\n");
}
