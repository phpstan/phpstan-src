<?php

namespace FilterVar;

use function PHPStan\Testing\assertType;

function doBar($mixed) {
    filter_var($mixed, FILTER_VALIDATE_FLOAT);
    assertType('mixed', $mixed);

	if (filter_var($mixed, FILTER_VALIDATE_FLOAT)) {
		assertType('float', $mixed);
	}
	assertType('mixed', $mixed);

	if (filter_var($mixed, FILTER_VALIDATE_FLOAT) === true) {
		assertType('float', $mixed);
	}
	assertType('mixed', $mixed);

	if (filter_var($mixed, FILTER_VALIDATE_FLOAT) === false) {
		assertType('mixed', $mixed); // could be mixed~float
	}
	assertType('mixed', $mixed);

	if (filter_var($mixed, FILTER_VALIDATE_FLOAT)) {
		assertType('float', $mixed);
		return;
	}
	assertType('mixed', $mixed); // could be mixed~float
}

function doFoo($mixed) {
	if (filter_var($mixed, FILTER_VALIDATE_FLOAT)) {
		assertType('float', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_INT)) {
		assertType('int', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_BOOL)) {
		assertType('bool', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_BOOLEAN)) {
		assertType('bool', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_URL)) {
		assertType('non-empty-string', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_DOMAIN)) {
		assertType('non-empty-string', $mixed);
	}
	if (filter_var($mixed, FILTER_VALIDATE_EMAIL)) {
		assertType('non-empty-string', $mixed);
	}
}

function doString(string $s) {
	if (filter_var($s, FILTER_VALIDATE_URL)) {
		assertType('non-empty-string', $s);
	}
	if (filter_var($s, FILTER_VALIDATE_DOMAIN)) {
		assertType('non-empty-string', $s);
	}
	if (filter_var($s, FILTER_VALIDATE_EMAIL)) {
		assertType('non-empty-string', $s);
	}
}
