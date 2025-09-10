<?php // lint < 8.3

namespace bug13481BeforePhp83;

use function PHPStan\Testing\assertType;

function bug13481() {
	$s = 'ab c1';
	assertType("'ab c2'", str_increment($s));

	++$s;
	assertType("'ab c2'", $s);
}

function bug13481b() {
	$s = '%';
	assertType("'%'", str_increment($s));

	++$s;
	assertType("'%'", $s);
}
