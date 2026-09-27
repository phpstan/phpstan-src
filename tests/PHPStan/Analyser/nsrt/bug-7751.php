<?php

namespace Bug7751;

use function PHPStan\Testing\assertType;

// the issue's top-level snippet inside a function: by-ref uses are followed
// to where the closure is invoked only in function-like bodies
function doFoo(): void
{
	$foo = false;

	$test = function() use (&$foo) {
		assertType('array{}', $foo);
		if (is_array($foo)) {
			echo 'array';
		}
		else {
			echo 'not array';
		}
	};

	$foo = [];

	$test();
	assertType('Closure(): void', $test);
}
