<?php

namespace LooseConstComparisonPhp8;

use function PHPStan\Testing\assertType;

function doFoo() {
	assertType('true', 0 == "0");
	assertType('true', 0 == "0.0");
	assertType('false', 0 == "foo");
	assertType('false', 0 == "");
	assertType('true', 42 == " 42");
	assertType('false', 42 == "42foo");

	assertType('false', 0.0 == "");
}
