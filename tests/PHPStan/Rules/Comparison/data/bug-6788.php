<?php

namespace Bug6788;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $nonES
 * @param non-falsy-string $nonFalsyString
 * @param numeric-string $numericString
 */
function strTypes($nonES, $nonFalsyString, $numericString) {
	if (chdir($nonES)) {
	}
	if (chdir($nonFalsyString)) {
	}
	if (chdir($numericString)) {
	}
}
