<?php

namespace StrlenNever;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonES
 * @param non-falsy-string $nonFalsy
 * @param numeric-string $numericString
 * @param lowercase-string $lower
 * @param uppercase-string $upper
 */
function doFoo(string $s, $nonES, $nonFalsy, $numericString, $lower, $upper) {
	if (strlen($s) <= 0) {
		assertType("''", $s);
	}
	if (strlen($nonES) <= 0) {
		assertType('*NEVER*', $nonES);
	}
	if (strlen($nonFalsy) <= 0) {
		assertType('*NEVER*', $nonFalsy);
	}
	if (strlen($numericString) <= 0) {
		assertType("*NEVER*", $numericString);
	}
	if (strlen($lower) <= 0) {
		assertType("''", $lower);
	}
	if (strlen($upper) <= 0) {
		assertType("''", $upper);
	}

	if (strlen($nonES) >= 0) {
		assertType('non-empty-string', $nonES);
	} else {
		assertType('*NEVER*', $nonES);
	}
}


function doBar(string $m): void
{
	if (strlen($m) >= 1) {
		if (strlen($m) <= 0) {
			assertType('*NEVER*', $m);
		}
	}
}

