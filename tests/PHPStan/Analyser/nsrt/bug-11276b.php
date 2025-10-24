<?php // lint >= 8.0

namespace Bug11276b;

use function PHPStan\Testing\assertType;

function doBar($j, array $arr) {
	$i = 1;
	if (!array_key_exists($i, $arr)) {
		if (array_key_exists($i, $arr) || array_key_exists($j, $arr)) {
			assertType('non-empty-array<mixed~1, mixed>', $arr);
		}
	}

	if (!array_key_exists($i, $arr)) {
		if (array_key_exists($j, $arr) || array_key_exists($i, $arr)) {
			assertType('non-empty-array<mixed~1, mixed>', $arr);
		}
	}
}
