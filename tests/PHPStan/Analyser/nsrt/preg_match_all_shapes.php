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
	assertType("array{}|array{string, non-empty-string, ''|numeric-string}", $matches);
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches)) {
		assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>, suffix: list<non-empty-string>, 2: list<non-empty-string>}", $matches);
	}
};

function (string $size): void {
	if (preg_match_all('/ab(?P<num>\d+)(?P<suffix>ab)?/', $size, $matches, PREG_SET_ORDER)) {
		assertType("list<array{string, num: numeric-string, 1: numeric-string, suffix: non-empty-string, 2: non-empty-string}", $matches);
	}
};
