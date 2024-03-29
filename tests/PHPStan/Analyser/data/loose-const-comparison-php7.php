<?php

namespace LooseConstComparisonPhp7;

use function PHPStan\Testing\assertType;

function doFoo() {
	assertType('true', 0 == "0");
	assertType('true', 0 == "0.0");
	assertType('true', 0 == "foo");
	assertType('true', 0 == "");
	assertType('true', 42 == " 42");
	assertType('true', 42 == "42foo");

	assertType('true', 0.0 == "");
	assertType('true', 42.0 == "42foo");
	assertType('true', 42 == "42.0foo");
	assertType('false', 42.1 == "42.0foo");
	assertType('true', 42.0 == "42.0foo");
}
