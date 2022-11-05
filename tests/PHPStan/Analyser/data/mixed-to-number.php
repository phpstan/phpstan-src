<?php

namespace MixedToNumber;

use function PHPStan\Testing\assertType;

/**
 * @param numeric-string $numericS
 */
function doFoo($mixed, int $i, float $f, $numericS) {
	// https://3v4l.org/ekcLT
	assertType('float|int', $numericS + $numericS);
	assertType('int', $i + $i);
	assertType('float', $f + $f);
	assertType('float', $f + $numericS);
	assertType('float|int', $i + $numericS);
	assertType('float', $i + $f);

	if (!is_float($mixed)) {
		// mixed can still be a numeric-string
		assertType('mixed~float', $mixed);
		assertType('(array|float|int)', $mixed + $mixed);
	}

	if (!is_int($mixed)) {
		// mixed can still be a integer number, when sum a bool
		assertType('mixed~int', $mixed);
		assertType('(array|float|int)', $mixed + $mixed);
	}

	if (!is_bool($mixed)) {
		// mixed can still be a integer number
		assertType('mixed~bool', $mixed);
		assertType('(array|float|int)', $mixed + $mixed);
	}

	if (!is_array($mixed)) {
		assertType('mixed~array', $mixed);
		assertType('(float|int)', $mixed + $mixed);
	}
}

function addingAlphabet() {
	$a = 'a';
	$a++;
	assertType("'b'", $a);
}
