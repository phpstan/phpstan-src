<?php declare(strict_types = 1);

namespace Bug14750;

use function PHPStan\Testing\assertType;

function pregMatchDecimalIntStringTypeMatches(string $x): void
{
	if (preg_match('/^(-?[0-9]+)$/', $x, $matches)) {
		assertType('decimal-int-string', $matches[1]);
	}

	if (preg_match('/^([3-9]+)$/', $x, $matches)) {
		assertType('decimal-int-string', $matches[1]);
	}

	if (preg_match('/^(\d+)$/', $x, $matches)) {
		assertType('decimal-int-string', $matches[1]);
	}

	if (preg_match('/^([3-9])$/', $x, $matches)) {
		assertType('decimal-int-string', $matches[1]);
	}

	// a negated digit class does not match a decimal integer
	if (preg_match('/^([^0-9])$/', $x, $matches)) {
		assertType('non-empty-string', $matches[1]);
	}
}

function edgeCases(string $x): void
{
	// zero-or-more digits can also match the empty string
	if (preg_match('/^(\d*)$/', $x, $matches)) {
		assertType("''|decimal-int-string", $matches[1]);
	}

	// a required leading minus and digits is always non-falsy
	if (preg_match('/^(-[0-9]+)$/', $x, $matches)) {
		assertType('decimal-int-string&non-falsy-string', $matches[1]);
	}

	// a minus that is not a leading sign does not yield a decimal integer
	if (preg_match('/^(\d+-\d+)$/', $x, $matches)) {
		assertType('non-falsy-string', $matches[1]);
	}

	// a plus sign is not part of a decimal integer string
	if (preg_match('/^([+-]?\d+)$/', $x, $matches)) {
		assertType('non-empty-string', $matches[1]);
	}

	// a negated digit class never yields a decimal integer
	if (preg_match('/^([^0-9]+)$/', $x, $matches)) {
		assertType('non-empty-string', $matches[1]);
	}
}
