<?php

use function PHPStan\Testing\assertType;

function sayEqualArrayShape(int $i, $arr): void
{
	if (false || array_key_exists($i, $arr)) {
		assertType('non-empty-array', $arr);
	}

	if (array_key_exists($i, $arr) || false) {
		assertType('non-empty-array', $arr);
	}

	if (true || array_key_exists($i, $arr)) {
		assertType('mixed', $arr);
	}

	if (array_key_exists($i, $arr) || true) {
		assertType('mixed', $arr);
	}

	if (array_key_exists($i, $arr)) {
		assertType('non-empty-array', $arr);
	}
}
