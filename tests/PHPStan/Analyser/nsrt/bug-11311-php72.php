<?php // lint < 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;

function doFoo() {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', '12.5', $matches, PREG_UNMATCHED_AS_NULL)) {
        // on PHP < 7.4, unmatched-as-null does not return null values; see https://3v4l.org/v3HE4
		assertType('array{0: string, major: string, 1: string, minor: string, 2: string, patch?: string, 3?: string}', $matches);
	}
}
