<?php

namespace Bug12297;

use function PHPStan\Testing\assertType;

function doFoo($s) {
	if (preg_match('/(^.+)/', $s, $matches) === 1) {
		assertType('non-empty-string', $matches[1]);
	}

	if (preg_match('/(.+$)/', $s, $matches) === 1) {
		assertType('non-empty-string', $matches[1]);
	}

	if (preg_match('/(^.+$)/', $s, $matches) === 1) {
		assertType('non-empty-string', $matches[1]);
	}
}
