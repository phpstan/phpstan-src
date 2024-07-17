<?php // lint < 7.4

namespace Bug11311Php72;

use function PHPStan\Testing\assertType;

// on PHP < 7.4, unmatched-as-null does not return null values; see https://3v4l.org/v3HE4

function doFoo(string $s) {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, major: numeric-string, 1: numeric-string, minor: numeric-string, 2: numeric-string, patch?: numeric-string, 3?: numeric-string}', $matches);
	}
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, 1?: non-empty-string, 2?: non-empty-string, 3?: non-empty-string}', $matches);
	}
	assertType('array{}|array{0: string, 1?: non-empty-string, 2?: non-empty-string, 3?: non-empty-string}', $matches);
}

// see https://3v4l.org/VeDob#veol
function unmatchedAsNullWithOptionalGroup(string $s): void {
	if (preg_match('/Price: (£|€)?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, 1?: non-empty-string}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{0: string, 1?: non-empty-string}', $matches);
}
