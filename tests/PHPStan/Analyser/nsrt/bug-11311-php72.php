<?php // lint < 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;

// on PHP < 7.4, unmatched-as-null does not return null values; see https://3v4l.org/v3HE4

function doFoo(string $s) {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, major: string, 1: string, minor: string, 2: string, patch?: string, 3?: string}', $matches);
	}
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, 1?: string, 2?: string, 3?: string}', $matches);
	}
	assertType('array{}|array{0: string, 1?: string, 2?: string, 3?: string}', $matches);
}

