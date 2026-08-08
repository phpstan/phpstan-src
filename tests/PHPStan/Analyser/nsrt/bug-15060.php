<?php declare(strict_types = 1);

namespace Bug15060;

use function PHPStan\Testing\assertType;

function search($searchParams): void
{
	if (isset($searchParams['test'])) {
		assertType('mixed~null', $searchParams['test']);
		if ($searchParams["test"]) {
			assertType('mixed~(0|0.0|\'\'|\'0\'|array{}|false|null)', $searchParams['test']);
			assertType('mixed~(0|0.0|\'\'|\'0\'|array{}|false|null)', $searchParams["test"]);
		}

		if (is_array($searchParams["test"])) {
			assertType('array<mixed, mixed>', $searchParams['test']);
			assertType('array<mixed, mixed>', $searchParams["test"]);
		}

		if (is_array($searchParams["test"]) && $searchParams["test"]) {
			assertType('non-empty-array<mixed, mixed>', $searchParams['test']);
			assertType('non-empty-array<mixed, mixed>', $searchParams["test"]);
		}
	}
}

function heredocAndNowdoc($a): void
{
	if (is_array($a['test'])) {
		assertType('array<mixed, mixed>', $a["test"]);
		assertType('array<mixed, mixed>', $a[<<<'TEST'
test
TEST]);
		assertType('array<mixed, mixed>', $a[<<<TEST
test
TEST]);
	}
}

function escapeSequences($a): void
{
	if (is_array($a["a\nb"])) {
		assertType('array<mixed, mixed>', $a[<<<TEST
a
b
TEST]);
	}
}

function interpolatedString($a, string $s): void
{
	if (is_array($a["x$s"])) {
		assertType('array<mixed, mixed>', $a[<<<TEST
x$s
TEST]);
	}
}

function integerBases($a): void
{
	if (is_array($a[1])) {
		assertType('array<mixed, mixed>', $a[0x1]);
		assertType('array<mixed, mixed>', $a[0b1]);
		assertType('array<mixed, mixed>', $a[01]);
	}
}

function arraySyntax($a, string $s): void
{
	if (is_array($a[[$s][0]])) {
		assertType('array<mixed, mixed>', $a[array($s)[0]]);
	}
}

function doubleCast($a, float $f): void
{
	if (is_array($a[(float) $f])) {
		assertType('array<mixed, mixed>', $a[(double) $f]);
	}
}

function constantCase($a): void
{
	if (is_array($a[true])) {
		assertType('array<mixed, mixed>', $a[TRUE]);
		assertType('array<mixed, mixed>', $a[\true]);
	}
	if (is_array($a[null])) {
		assertType('array<mixed, mixed>', $a[NULL]);
	}
	if (is_array($a[false])) {
		assertType('array<mixed, mixed>', $a[FALSE]);
	}
}
