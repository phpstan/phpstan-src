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
	assertType("array{0: list<string>, num: list<''|numeric-string>, 1: list<''|numeric-string>}", $matches);
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
