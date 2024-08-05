<?php // lint >= 7.2

namespace PregMatchAllShapes;

use function PHPStan\Testing\assertType;

function (string $size): void {
	preg_match_all('/ab(\d+)?/', $size, $matches, PREG_UNMATCHED_AS_NULL);
	assertType('array{list<string>, list<numeric-string|null>}', $matches);
};

function (string $size): void {
	preg_match_all('/ab(?P<num>\d+)?/', $size, $matches);
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	preg_match_all('/ab(\d+)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER);
	assertType('array{list<string>, list<numeric-string|null>}', $matches);
};

function (string $size): void {
	preg_match_all('/ab(?P<num>\d+)?/', $size, $matches, PREG_PATTERN_ORDER);
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches)) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) > 0) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	} else {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	}
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) != false) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) == true) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	} else {
		assertType("array{}", $matches);
	}
	assertType("array{}|array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) === 1) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	} else {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
	}
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	preg_match_all('/a(b)(\d+)?/', $size, $matches, PREG_SET_ORDER);
	assertType("list<array{0: string, 1: 'b', 2?: numeric-string}>", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<''|'ab'>, 2: list<''|'ab'>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<'ab'|null>, 2: list<'ab'|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER)) {
		assertType("list<array{0: string, num: numeric-string, 1: numeric-string, suffix?: 'ab', 2?: 'ab'}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<''|'ab'>, 2: list<''|'ab'>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER)) {
		assertType("list<array{0: string, num: numeric-string, 1: numeric-string, suffix: 'ab'|null, 2: 'ab'|null}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<'ab'|null>, 2: list<'ab'|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("list<array{0: array{string, int<0, max>}, num: array{numeric-string, int<0, max>}, 1: array{numeric-string, int<0, max>}, suffix?: array{'ab', int<0, max>}, 2?: array{'ab', int<0, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: list<array{string, int<0, max>}>, num: list<array{numeric-string, int<0, max>}>, 1: list<array{numeric-string, int<0, max>}>, suffix: list<''|array{'ab', int<0, max>}>, 2: list<''|array{'ab', int<0, max>}>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("list<array{0: array{string|null, int<-1, max>}, num: array{numeric-string|null, int<-1, max>}, 1: array{numeric-string|null, int<-1, max>}, suffix: array{'ab'|null, int<-1, max>}, 2: array{'ab'|null, int<-1, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: list<array{string|null, int<-1, max>}>, num: list<array{numeric-string|null, int<-1, max>}>, 1: list<array{numeric-string|null, int<-1, max>}>, suffix: list<array{'ab'|null, int<-1, max>}>, 2: list<array{'ab'|null, int<-1, max>}>}", $matches);
	}
};

class Bug11457
{
	public function sayHello(string $content): void
	{
		if (preg_match_all("~text=~mU", $content, $matches, PREG_OFFSET_CAPTURE) === 0) {
			return;
		}

		assertType('array{list<array{string, int<0, max>}>}', $matches);
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
}
