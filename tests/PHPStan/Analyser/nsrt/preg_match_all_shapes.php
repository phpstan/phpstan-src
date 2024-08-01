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
		return;
	}
	assertType("array{}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) > 0) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
		return;
	}
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) != false) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
		return;
	}
	assertType("array{}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) == true) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
		return;
	}
	assertType("array{}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)?/', $size, $matches) === 1) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
		return;
	}
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
};

function (string $size): void {
	preg_match_all('/a(b)(\d+)?/', $size, $matches, PREG_SET_ORDER);
	assertType("list<array{0: string, 1: non-empty-string, 2?: numeric-string}>", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<string>, 2: list<string>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<non-empty-string|null>, 2: list<non-empty-string|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER)) {
		assertType("list<array{0: string, num: numeric-string, 1: numeric-string, suffix?: non-empty-string, 2?: non-empty-string}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<string>, 2: list<string>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER)) {
		assertType("list<array{0: string, num: numeric-string, 1: numeric-string, suffix: non-empty-string|null, 2: non-empty-string|null}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER)) {
		assertType("array{0: list<string>, num: list<numeric-string>, 1: list<numeric-string>, suffix: list<non-empty-string|null>, 2: list<non-empty-string|null>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("list<array{0: array{string, int<0, max>}, num: array{numeric-string, int<0, max>}, 1: array{numeric-string, int<0, max>}, suffix?: array{non-empty-string, int<0, max>}, 2?: array{non-empty-string, int<0, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: list<array{string, int<0, max>}>, num: list<array{numeric-string, int<0, max>}>, 1: list<array{numeric-string, int<0, max>}>, suffix: list<''|array{non-empty-string, int<0, max>}>, 2: list<''|array{non-empty-string, int<0, max>}>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("list<array{0: array{string|null, int<-1, max>}, num: array{numeric-string|null, int<-1, max>}, 1: array{numeric-string|null, int<-1, max>}, suffix: array{non-empty-string|null, int<-1, max>}, 2: array{non-empty-string|null, int<-1, max>}}>", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_UNMATCHED_AS_NULL|PREG_PATTERN_ORDER|PREG_OFFSET_CAPTURE)) {
		assertType("array{0: list<array{string|null, int<-1, max>}>, num: list<array{numeric-string|null, int<-1, max>}>, 1: list<array{numeric-string|null, int<-1, max>}>, suffix: list<array{non-empty-string|null, int<-1, max>}>, 2: list<array{non-empty-string|null, int<-1, max>}>}", $matches);
	}
};
