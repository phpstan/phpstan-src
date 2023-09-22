<?php

namespace PregGrep;

use function PHPStan\Testing\assertType;

function doFoo(array $arr, $mixed, $flags) {
	assertType('array|false', preg_grep($mixed, $arr));
	assertType('array|false', preg_grep($mixed, $arr, $flags));
	assertType('array', preg_grep('/^[0-9]+$/', $arr));
	assertType('array', preg_grep('/^[0-9]+$/', $arr, $flags));

	// invalid pattern
	assertType('array|false', preg_grep('/((/', $arr));
}
