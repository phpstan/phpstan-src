<?php

namespace Bug5496;

use function PHPStan\Testing\assertType;

function doFoo() {
	/** @var array<string, 'auto'|'copy'> $propagation */
	$propagation = [];

	assertType('bool', \in_array('auto', $propagation, true));
}
