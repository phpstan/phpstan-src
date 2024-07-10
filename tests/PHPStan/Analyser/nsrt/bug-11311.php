<?php // lint >= 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;

function doFoo() {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', '12.5', $matches, PREG_UNMATCHED_AS_NULL)) {

		assertType('array{0: string, major: string|null, 1: string|null, minor: string|null, 2: string|null, patch: string|null, 3: string|null}', $matches);
	}
}
