<?php declare(strict_types=1);

namespace Bug14791;

use function PHPStan\Testing\assertType;

function subject(string $s): void
{
	// a series of digits can begin with "0" (e.g. "007"), so the whole match is a
	// numeric-string, not a decimal-int-string
	if (preg_match('/^\d+$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^[0-9]+$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^\d{2}$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^\d{1,6}$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^\d\d$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^0\d+$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^-?\d+$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}
	if (preg_match('/^-?\d$/', $s, $m)) {
		assertType('numeric-string', $m[0]);
	}

	// single digit, or a leading non-zero digit, stays a decimal-int-string
	if (preg_match('/^\d$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^[0-9]$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^0$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^[1-9][0-9]*$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^[1-9]+[0-9]*$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^-?[1-9]\d*$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
	if (preg_match('/^[1-5]$/', $s, $m)) {
		assertType('decimal-int-string', $m[0]);
	}
}

function captureGroups(string $s): void
{
	if (preg_match('/^(\d+)$/', $s, $m)) {
		assertType('numeric-string', $m[1]);
	}
	if (preg_match('/^(\d{2,})$/', $s, $m)) {
		assertType('non-falsy-string&numeric-string', $m[1]);
	}
	if (preg_match('/^(0\d+)$/', $s, $m)) {
		assertType('non-falsy-string&numeric-string', $m[1]);
	}
	if (preg_match('/^(\d*)$/', $s, $m)) {
		assertType("''|numeric-string", $m[1]);
	}
	if (preg_match('/^([1-9]\d*)$/', $s, $m)) {
		assertType('decimal-int-string&non-falsy-string', $m[1]);
	}
	if (preg_match('/^([1-9]+)$/', $s, $m)) {
		assertType('decimal-int-string', $m[1]);
	}
	if (preg_match('/^(\d)$/', $s, $m)) {
		assertType('decimal-int-string', $m[1]);
	}
}
