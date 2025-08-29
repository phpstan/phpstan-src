<?php

namespace PreDec;

use function PHPStan\Testing\assertType;

function doFoo() {
	$s = '';
	--$s;
	assertType("-1", $s);
}

function doFoo2() {
	$s = '123';
	--$s;
	assertType("122", $s);
}

function doFooBar() {
	$s = 'abc';
	--$s;
	assertType("'abc'", $s);
}
