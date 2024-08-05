<?php // lint >= 7.4

namespace Bug11311;

use function PHPStan\Testing\assertType;
use InvalidArgumentException;

function doFoo(string $s) {
	if (1 === preg_match('/(?<major>\d+)\.(?<minor>\d+)(?:\.(?<patch>\d+))?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {

		assertType('array{0: string, major: numeric-string, 1: numeric-string, minor: numeric-string, 2: numeric-string, patch: numeric-string|null, 3: numeric-string|null}', $matches);
	}
}

function doUnmatchedAsNull(string $s): void {
	if (preg_match('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType("array{string, 'foo'|null, 'bar'|null, 'baz'|null}", $matches);
	}
	assertType("array{}|array{string, 'foo'|null, 'bar'|null, 'baz'|null}", $matches);
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
			assertType("array{string, '£'|null, '€'|null}", $matches); // could be tagged union
		}
	}

	function doBar(string $s): void {
		if (preg_match('/Price: (?:(£)|(€))?\d+/', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
			assertType("array{string, '£'|null, '€'|null}", $matches); // could be tagged union
		}
	}
}

function (string $size): void {
	if (preg_match('/ab(\d){2,4}xx([0-9])?e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, numeric-string, numeric-string|null}', $matches);
};

function (string $size): void {
	if (preg_match('/a(\dAB){2}b(\d){2,4}([1-5])([1-5a-z])e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-empty-string, numeric-string, numeric-string, non-empty-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(ab(\d)){2,4}xx([0-9][a-c])?e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-empty-string, numeric-string, non-empty-string|null}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\d+)e(\d?)/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType("array{string, numeric-string, ''|numeric-string}", $matches);
};

function (string $size): void {
	if (preg_match('/ab(?P<num>\d+)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{0: string, num: numeric-string, 1: numeric-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\d\d)/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string&numeric-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\d+\s)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\s)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-empty-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\S)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-empty-string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\S?)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, string}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\S)?e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-empty-string|null}', $matches);
};

function (string $size): void {
	if (preg_match('/ab(\d+\d?)e?/', $size, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
		throw new InvalidArgumentException(sprintf('Invalid size "%s"', $size));
	}
	assertType('array{string, non-falsy-string&numeric-string}', $matches);
};

function (string $s): void {
	if (preg_match('/Price: ([2-5])/i', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{string, numeric-string}', $matches);
	}
};

function (string $s): void {
	if (preg_match('/Price: ([2-5A-Z])/i', $s, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType('array{string, non-empty-string}', $matches);
	}
};

function (string $s): void {
	if (preg_match('/^%([0-9]*\$)?[0-9]*\.?[0-9]*([sbdeEfFgGhHouxX])$/', $s, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
		assertType("array{string, non-falsy-string|null, 'b'|'d'|'E'|'e'|'F'|'f'|'G'|'g'|'H'|'h'|'o'|'s'|'u'|'X'|'x'}", $matches);
	}
};

function (string $s): void {
	if (preg_match('/(?<whitespace>\s*)(?<value>.*)/', $s, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
		assertType('array{0: string, whitespace: string, 1: string, value: string, 2: string}', $matches);
	}
};

function (string $s): void {
	preg_match('/%a(\d*)/', $s, $matches, PREG_UNMATCHED_AS_NULL);
	assertType("array{0?: string, 1?: ''|numeric-string|null}", $matches); // could be array{0?: string, 1?: ''|numeric-string}
};

function (string $s): void {
	preg_match('/%a(\d*)?/', $s, $matches, PREG_UNMATCHED_AS_NULL);
	assertType("array{0?: string, 1?: ''|numeric-string|null}", $matches); // could be array{0?: string, 1?: ''|numeric-string}
};
