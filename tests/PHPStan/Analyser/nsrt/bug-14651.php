<?php declare(strict_types=1);

namespace Bug14651;

use function PHPStan\Testing\assertType;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

/**
 * @return list<string>
 */
function readLines(string $buffer): array
{
	$result = [];

	while (str_contains($buffer, "\n")) {
		assertType('array{string, string}', explode("\n", $buffer, 2));
		[$first, $rest] = explode("\n", $buffer, 2);
		$result[] = $first;
		$buffer = $rest;
	}

	return $result;
}

function inIf(string $s): void
{
	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		assertType('array{string, string}', explode("\n", $s, 2));
		assertType('array{string, string, ...<string>}', explode("\n", $s));
		assertType('array{0: string, 1: string, 2?: string}', explode("\n", $s, 3));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function startsWith(string $s): void
{
	if (str_starts_with($s, "\n")) {
		assertType('non-falsy-string', $s);
		assertType('array{string, string}', explode("\n", $s, 2));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function endsWith(string $s): void
{
	if (str_ends_with($s, "\n")) {
		assertType('non-falsy-string', $s);
		assertType('array{string, string}', explode("\n", $s, 2));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

/**
 * @param non-empty-string $needle
 */
function variableNeedle(string $s, string $needle): void
{
	if (str_contains($s, $needle)) {
		assertType('non-empty-string', $s);
		assertType('array{string, string}', explode($needle, $s, 2));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function notGuaranteed(string $s): void
{
	// no guard at all
	assertType('non-empty-list<string>', explode("\n", $s, 2));

	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		// different delimiter than the proven substring
		assertType('non-empty-list<string>', explode(",", $s, 2));
		// limit 1 always yields a single element
		assertType('non-empty-list<string>', explode("\n", $s, 1));
		// negative limit may drop the guaranteed parts
		assertType('list<string>', explode("\n", $s, -1));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function unknownLimit(string $s, int $limit): void
{
	// a limit that is not proven to be >= 2 falls back regardless of the guard
	assertType('list<string>', explode("\n", $s, $limit));

	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		assertType('list<string>', explode("\n", $s, $limit));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

/**
 * @param int<2, 4> $bounded
 * @param int<5, max> $unbounded
 * @param int<-2, 3> $negativeAndPositive
 * @param int<-5, -1> $alwaysNegative
 * @param int<min, -1> $unboundedNegative
 */
function rangeLimit(string $s, int $bounded, int $unbounded, int $negativeAndPositive, int $alwaysNegative, int $unboundedNegative): void
{
	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		// a bounded range yields optional keys up to its maximum
		assertType('array{0: string, 1: string, 2?: string, 3?: string}', explode("\n", $s, $bounded));
		// an unbounded range stays a >= 2 list
		assertType('array{string, string, ...<string>}', explode("\n", $s, $unbounded));
		// ranges that may be negative are not proven to yield two elements
		assertType('list<string>', explode("\n", $s, $negativeAndPositive));
		assertType('list<string>', explode("\n", $s, $alwaysNegative));
		assertType('list<string>', explode("\n", $s, $unboundedNegative));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function zeroAndNegativeLimit(string $s): void
{
	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		// limit 0 is treated as 1, so a single non-empty element
		assertType('non-empty-list<string>', explode("\n", $s, 0));
		// a negative limit drops trailing components and may empty the result
		assertType('list<string>', explode("\n", $s, -1));
		assertType('list<string>', explode("\n", $s, -2));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}

function largeConstantLimit(string $s): void
{
	if (str_contains($s, "\n")) {
		assertType('non-falsy-string', $s);
		// a limit larger than the array builder handles stays a >= 2 list
		assertType('array{string, string, ...<string>}', explode("\n", $s, 100000));
	} else {
		assertType('string', $s);
	}

	assertType('string', $s);
}
