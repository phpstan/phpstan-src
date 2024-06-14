<?php

namespace ArrayOffsetUnset;

use function PHPStan\Testing\assertType;

/**
 * @param array<0|1, mixed> $list
 */
function foo(array $list) {
	assertType('array<0|1, mixed>', $list);
	unset($list[0]);
	assertType('array<1, mixed>', $list);
	unset($list[1]);
	assertType('array{}', $list);
}
