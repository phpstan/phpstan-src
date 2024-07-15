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

function bug11331a(string $url):void {
	// group a is actually optional as the entire (?:...) around it is optional
	if (preg_match('{^
	(?:
		(?<a>.+)
	)?
	(?<b>.+)}mix', $url, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, a: string|null, 1: string|null, b: string, 2: string}', $matches);
	}
}

function bug11331b(string $url):void {
	if (preg_match('{^
	(?:
		(?<a>.+)
	)?
	(?<b>.+)?}mix', $url, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, a: string|null, 1: string|null, b: string|null, 2: string|null}', $matches);
	}
}

function bug11331c(string $url):void {
	if (preg_match('{^
	(?:
		(?:https?|git)://([^/]+)/ (?# group 1 here can be null if group 2 matches)
		|                         (?# the alternation making it so that only either should match)
		git@([^:]+):/?            (?# group 2 here can be null if group 1 matches)
	)
	([^/]+)
	/
	([^/]+?)
	(?:\.git|/)?
$}x', $url, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{string, string|null, string|null, string, string}', $matches);
	}
}

class UnmatchedAsNullWithTopLevelAlternation {
	function doFoo(string $s): void {
		if (preg_match('/Price: (?:(£)|(€))\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType('array{string, string|null, string|null}', $matches); // could be array{0: string, 1: null, 2: string}|array{0: string, 1: string, 2: null}
		}
	}

	function doBar(string $s): void {
		if (preg_match('/Price: (?:(£)|(€))?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType('array{string, string|null, string|null}', $matches);
		}
	}
}
