<?php // onlyif PHP_VERSION_ID >= 80100

namespace ArrayIsListUnset;

use function PHPStan\Testing\assertType;

function () {
	$a = [];
	assertType('array{}', $a);
	assertType('true', array_is_list($a));
	$a[] = 1;
	assertType('array{1}', $a);
	assertType('true', array_is_list($a));
	$a[] = 2;
	assertType('array{1, 2}', $a);
	assertType('true', array_is_list($a));
	$a[] = 3;
	assertType('array{1, 2, 3}', $a);
	assertType('true', array_is_list($a));
	unset($a[2]);
	assertType('array{1, 2}', $a);
	assertType('false', array_is_list($a));
	$a[] = 4;
	assertType('array{0: 1, 1: 2, 3: 4}', $a);
	assertType('false', array_is_list($a));
};
