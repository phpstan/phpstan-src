<?php

namespace Bug11276;

use function PHPStan\Testing\assertType;

function doFoo(int $i, int $j, $arr): void
{
	if (false || array_key_exists($i, $arr)) {
		assertType('non-empty-array', $arr);
	}

	if (array_key_exists($i, $arr) || false) {
		assertType('non-empty-array', $arr);
	}

	if ("0" || array_key_exists($i, $arr)) {
		assertType('non-empty-array', $arr);
	}

	if (array_key_exists($i, $arr) || "0") {
		assertType('non-empty-array', $arr);
	}

	if (true || array_key_exists($i, $arr)) {
		assertType('mixed', $arr);
	}

	if (array_key_exists($i, $arr) || true) {
		assertType('mixed', $arr);
	}

	if ("1" || array_key_exists($i, $arr)) {
		assertType('mixed', $arr);
	}

	if (array_key_exists($i, $arr) || "1") {
		assertType('mixed', $arr);
	}

	if (array_key_exists($i, $arr) || array_key_exists($j, $arr)) {
		assertType('non-empty-array', $arr);
	}

	if (!array_key_exists($j, $arr)) {
		if (array_key_exists($i, $arr) || array_key_exists($j, $arr)) {
			assertType('non-empty-array', $arr);
		}
	}
	if (!array_key_exists($i, $arr)) {
		if (array_key_exists($i, $arr) || array_key_exists($j, $arr)) {
			assertType('non-empty-array', $arr);
		}
	}

	if (array_key_exists($i, $arr)) {
		assertType('non-empty-array', $arr);
	}
}
