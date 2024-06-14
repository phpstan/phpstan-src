<?php

namespace Bug7764;

use function PHPStan\Testing\assertType;

function doFoo() {
	$split = sscanf('hello/world', '%[^/]/%[^/]/%s');
	assertType('array{string|null, string|null, string|null}|null', $split);
	if (!is_array($split)) {
		echo 'Not array', "\n";
	} elseif (count($split) > 1) {
		echo 'Success', "\n";
	}
	print_r($split);
}

