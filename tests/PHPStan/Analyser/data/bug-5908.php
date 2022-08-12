<?php

namespace Bug5908;

use function PHPStan\Testing\assertType;

function foo($mixed, string $s) {
	if (ctype_digit($mixed)) {
		assertType('float|int|numeric-string', $mixed);
	}
	assertType('mixed', $mixed);

	if (ctype_digit($s)) {
		assertType('numeric-string', $s);
	}

	if (ctype_digit($mixed)) {
		assertType('float|int|numeric-string', $mixed);
		return;
	}
	assertType('mixed~float|int', $mixed);
}
