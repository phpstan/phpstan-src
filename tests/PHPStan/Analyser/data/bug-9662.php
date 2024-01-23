<?php declare(strict_types = 1);

namespace Bug9662;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $a
 * @return void
 */
function doFoo($a) {
	if (in_array('foo', $a, true)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array<mixed~'foo'>", $a);
	}
	assertType('array', $a);

	if (in_array('foo', $a, false)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array('foo', $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);
}
