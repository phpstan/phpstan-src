<?php // lint >= 8.3

namespace bug13481Php83;

use function PHPStan\Testing\assertType;
use function str_increment;

function bug13481() {
	$s = 'ab c1';
	assertType("*ERROR*", str_increment($s));

	++$s;
	assertType("*ERROR*", $s);
}

function bug13481b() {
	$s = '%';
	assertType("*ERROR*", str_increment($s));

	++$s;
	assertType("*ERROR*", $s);
}

