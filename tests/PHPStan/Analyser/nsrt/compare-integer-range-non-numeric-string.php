<?php

namespace CompareIntegerRangeNonNumericString;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 10> $zeroToTen
 * @param int<2, 9> $twoToNine
 */
function doFoo($zeroToTen, $twoToNine): void
{
	// PHP 8 compares an integer with a non-numeric string as strings, which
	// does not keep the order of the integers: '10' < '1abc' < '2'
	assertType('bool', '1abc' < $zeroToTen);
	assertType('bool', '1abc' <= $zeroToTen);
	assertType('bool', '1abc' > $zeroToTen);
	assertType('bool', '1abc' >= $zeroToTen);
	assertType('bool', $zeroToTen < '1abc');
	assertType('bool', $zeroToTen >= '1abc');
	assertType('true', $twoToNine > '1abc');
	assertType('false', $twoToNine <= '1abc');
}
