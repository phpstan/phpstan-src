<?php

namespace PreInc;

use function PHPStan\Testing\assertType;

function doFoo() {
	$s = '';
	++$s;
	assertType("'1'", $s);
}

function doFoo2() {
	$s = '123';
	++$s;
	assertType("124", $s);
}

function doFooBar() {
	$s = 'abc';
	++$s;
	assertType("'abd'", $s);
}
