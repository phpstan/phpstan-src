<?php

namespace LooseEqualityNarrowingPhp7;

use function PHPStan\Testing\assertType;

/**
 * @param mixed $mixed
 */
function doFoo(int $int, string $string, float $float, $mixed): void
{
	// PHP 7 compares a number with the number a string starts with
	if ($int == 'abc') {
		assertType('0', $int);
	}

	if ($int == '1abc') {
		assertType('1', $int);
	} else {
		assertType('int<min, 0>|int<2, max>', $int);
	}

	if ($string == 0) {
		assertType('string', $string);
	}

	if ($float == 'INF') {
		assertType('0.0', $float);
	}

	if ($mixed == 'abc') {
		assertType('mixed~null', $mixed);
	}

	if ($mixed == NAN) {
		assertType('true', $mixed);
	}

	assertType('bool', $int == 'abc');
	assertType('false', $int == 1.5);
}
