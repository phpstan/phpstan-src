<?php // lint >= 7.2

namespace PregMatchAllShapes;

use function PHPStan\Testing\assertType;

function (string $size): void {
	preg_match_all('/ab(\d+)?/', $size, $matches, PREG_UNMATCHED_AS_NULL);
	assertType('array{list<string>, list<decimal-int-string|null>}', $matches);
};

function (string $size): void {
	preg_match_all('/ab(?P<num>\d+)?/', $size, $matches);
	assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	preg_match_all('/ab(\d+)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER);
	assertType('array{list<string>, list<decimal-int-string|null>}', $matches);
};

function (string $size): void {
	preg_match_all('/ab(?P<num>\d+)?/', $size, $matches, PREG_PATTERN_ORDER);
	assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches)) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) > 0) {
		assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
	} else {
		assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
	}
	assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) != false) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) == true) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: non-empty-list<string>, num: non-empty-list<''|decimal-int-string>, 1: non-empty-list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) === 1) {
		assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
	} else {
		assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
	}
	assertType("array{0: list<string>, num: list<''|decimal-int-string>, 1: list<''|decimal-int-string>}", $matches);
};

function (string $size): void {
	preg_match_all('/a(b)(\d+)?/', $size, $matches, PREG_SET_ORDER);
	assertType("list<array{0: string, 1: 'b', 2?: decimal-int-string}>", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches)) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<decimal-int-string>, 1: non-empty-list<decimal-int-string>, suffix: non-empty-list<''|'ab'>, 2: non-empty-list<''|'ab'>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<decimal-int-string>, 1: non-empty-list<decimal-int-string>, suffix: non-empty-list<'ab'|null>, 2: non-empty-list<'ab'|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER)) {
		assertType("non-empty-list<array{0: string, num: decimal-int-string, 1: decimal-int-string, suffix?: 'ab', 2?: 'ab'}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER)) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<decimal-int-string>, 1: non-empty-list<decimal-int-string>, suffix: non-empty-list<''|'ab'>, 2: non-empty-list<''|'ab'>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER)) {
		assertType("non-empty-list<array{0: string, num: decimal-int-string, 1: decimal-int-string, suffix: 'ab'|null, 2: 'ab'|null}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER)) {
		assertType("array{0: non-empty-list<string>, num: non-empty-list<decimal-int-string>, 1: non-empty-list<decimal-int-string>, suffix: non-empty-list<'ab'|null>, 2: non-empty-list<'ab'|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("non-empty-list<array{0: array{string, int<-1, max>}, num: array{decimal-int-string, int<-1, max>}, 1: array{decimal-int-string, int<-1, max>}, suffix?: array{'ab', int<-1, max>}, 2?: array{'ab', int<-1, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: non-empty-list<array{string, int<-1, max>}>, num: non-empty-list<array{decimal-int-string, int<-1, max>}>, 1: non-empty-list<array{decimal-int-string, int<-1, max>}>, suffix: non-empty-list<array{''|'ab', int<-1, max>}>, 2: non-empty-list<array{''|'ab', int<-1, max>}>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("non-empty-list<array{0: array{string|null, int<-1, max>}, num: array{decimal-int-string|null, int<-1, max>}, 1: array{decimal-int-string|null, int<-1, max>}, suffix: array{'ab'|null, int<-1, max>}, 2: array{'ab'|null, int<-1, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: non-empty-list<array{string|null, int<-1, max>}>, num: non-empty-list<array{decimal-int-string|null, int<-1, max>}>, 1: non-empty-list<array{decimal-int-string|null, int<-1, max>}>, suffix: non-empty-list<array{'ab'|null, int<-1, max>}>, 2: non-empty-list<array{'ab'|null, int<-1, max>}>}", $matches);
	}
};

class Bug11457
{
	public function sayHello(string $content): void
	{
		if (preg_match_all("~text=~mU", $content, $matches, PREG_OFFSET_CAPTURE) === 0) {
			return;
		}

		assertType('array{list<array{string, int<-1, max>}>}', $matches);
	}

	public function sayFoo(string $content): void
	{
		if (preg_match_all("~text=~mU", $content, $matches, PREG_SET_ORDER) === 0) {
			return;
		}

		assertType('list<array{string}>', $matches);
	}

	public function sayBar(string $content): void
	{
		if (preg_match_all("~text=~mU", $content, $matches, PREG_PATTERN_ORDER) === 0) {
			return;
		}

		assertType('array{list<string>}', $matches);
	}

	function doFoobar(string $s): void {
		if (preg_match_all('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_OFFSET_CAPTURE)) {
			assertType("array{non-empty-list<array{string, int<-1, max>}>, non-empty-list<array{''|'foo', int<-1, max>}>, non-empty-list<array{''|'bar', int<-1, max>}>, non-empty-list<array{''|'baz', int<-1, max>}>}", $matches);
		}
	}

	function doFoobarNull(string $s): void {
		if (preg_match_all('/(foo)?(bar)?(baz)?/', $s, $matches, PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL)) {
			assertType("array{non-empty-list<array{string|null, int<-1, max>}>, non-empty-list<array{'foo'|null, int<-1, max>}>, non-empty-list<array{'bar'|null, int<-1, max>}>, non-empty-list<array{'baz'|null, int<-1, max>}>}", $matches);
		}
	}
}

function bug14781(string $s): void {
	if (preg_match_all('/(\d+)/', $s, $matches)) {
		assertType('array{non-empty-list<string>, non-empty-list<decimal-int-string>}', $matches);
		// accessing offset 0 is safe because the lists are non-empty
		assertType('string', $matches[0][0]);
		assertType('decimal-int-string', $matches[1][0]);
	}

	if (preg_match_all('/(\d+)/', $s, $setMatches, PREG_SET_ORDER)) {
		assertType('non-empty-list<array{string, decimal-int-string}>', $setMatches);
		assertType('array{string, decimal-int-string}', $setMatches[0]);
	}
}

function bug11661(): void {
	preg_match_all('/(ERR)?(.+)/', 'abc', $results, PREG_SET_ORDER);
	assertType("list<array{string, ''|'ERR', non-empty-string}>", $results);

	preg_match_all('/(ERR)?.+/', 'abc', $results, PREG_SET_ORDER);
	assertType("list<array{0: string, 1?: 'ERR'}>", $results);

}
