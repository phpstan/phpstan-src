<?php // lint >= 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;

function doFoo(string $s) {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {

		assertType('array{0: string, major: string, 1: string, minor: string, 2: string, patch: string|null, 3: string|null}', $matches);
	}
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{string, string|null, string|null, string|null}', $matches);
	}
	assertType('array{}|array{string, string|null, string|null, string|null}', $matches);
}

// see https://3v4l.org/VeDob
function unmatchedAsNullWithOptionalGroup(string $s): void {
	if (preg_match('/Price: (£|€)?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		// with PREG_UNMATCHED_AS_NULL the offset 1 will always exist. It is correct that it's nullable because it's optional though
		assertType('array{string, string|null}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string, string|null}', $matches);
}

