<?php

namespace PregMatchShapesPhp73;

use function PHPStan\Testing\assertType;

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array<string|null>', $matches);
	}
	assertType('array<string|null>', $matches);
}