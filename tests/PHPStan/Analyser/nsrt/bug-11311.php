<?php // lint >= 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;

function doFoo(string $s) {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {

		assertType('array{0: string, major: non-empty-string&numeric-string, 1: non-empty-string&numeric-string, minor: non-empty-string&numeric-string, 2: non-empty-string&numeric-string, patch: (non-empty-string&numeric-string)|null, 3: (non-empty-string&numeric-string)|null}', $matches);
	}
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{string, non-empty-string|null, non-empty-string|null, non-empty-string|null}', $matches);
	}
	assertType('array{}|array{string, non-empty-string|null, non-empty-string|null, non-empty-string|null}', $matches);
}

// see https://3v4l.org/VeDob
function unmatchedAsNullWithOptionalGroup(string $s): void {
	if (preg_match('/Price: (£|€)?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		// with PREG_UNMATCHED_AS_NULL the offset 1 will always exist. It is correct that it's nullable because it's optional though
		assertType('array{string, non-empty-string|null}', $matches);
	} else {
		assertType('array{}', $matches);
	}
	assertType('array{}|array{string, non-empty-string|null}', $matches);
}

function bug11331a(string $url):void {
	// group a is actually optional as the entire (?:...) around it is optional
	if (preg_match('{^
	(?:
		(?<a>.+)
	)?
	(?<b>.+)}mix', $url, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, a: non-empty-string|null, 1: non-empty-string|null, b: non-empty-string, 2: non-empty-string}', $matches);
	}
}

function bug11331b(string $url):void {
	if (preg_match('{^
	(?:
		(?<a>.+)
	)?
	(?<b>.+)?}mix', $url, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{0: string, a: non-empty-string|null, 1: non-empty-string|null, b: non-empty-string|null, 2: non-empty-string|null}', $matches);
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
		assertType('array{string, non-empty-string|null, non-empty-string|null, non-empty-string, non-empty-string}', $matches);
	}
}

class UnmatchedAsNullWithTopLevelAlternation {
	function doFoo(string $s): void {
		if (preg_match('/Price: (?:(£)|(€))\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType('array{string, non-empty-string|null, non-empty-string|null}', $matches); // could be array{0: string, 1: null, 2: string}|array{0: string, 1: string, 2: null}
		}
	}

	function doBar(string $s): void {
		if (preg_match('/Price: (?:(£)|(€))?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType('array{string, non-empty-string|null, non-empty-string|null}', $matches);
		}
	}
}
