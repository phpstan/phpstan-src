<?php

namespace Bug10528;

use function PHPStan\Testing\assertType;

function bug10528(string $string): void {
	$pos = strpos('*', $string);
	assert((bool) $pos);

	assertType('int<1, max>', $pos);

	$sub = substr($string, 0, $pos);
	assert($pos !== FALSE);
	$sub = substr($string, 0, $pos);
}

