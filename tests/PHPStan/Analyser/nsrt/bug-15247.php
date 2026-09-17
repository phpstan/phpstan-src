<?php declare(strict_types = 1);

namespace Bug15247;

use Generator;
use function PHPStan\Testing\assertType;

/**
 * @param list<int> $list
 * @return array<mixed>
 */
function spreadBeforeByRef(array $list, int $x): array
{
	$a = [...$list, &$x];
	$x = 5;
	assertType('non-empty-list<int>', $a);
	return $a;
}

/** @return array<mixed> */
function constantSpreadBeforeByRef(int $x): array
{
	$list = [10, 20];
	$a = [...$list, &$x];
	$x = 5;
	assertType('array{10, 20, 5}', $a);
	return $a;
}

function writeThroughConstantSpread(int $x): void
{
	$list = [10, 20];
	$a = [...$list, &$x];
	$a[2] = 'foo';
	assertType("'foo'", $x);
	assertType("array{10, 20, 'foo'}", $a);
}

/** @param list<int> $list */
function writeThroughUnknownSpread(array $list, int $x): void
{
	$a = [...$list, &$x];
	$a[0] = 'foo';

	// the reference lives at offset count($list), which might be 0
	assertType("'foo'|int", $x);
}

function emptySpread(int $x): void
{
	$list = [];
	$a = [...$list, &$x];
	$a[0] = 'foo';
	assertType("'foo'", $x);
	assertType("array{'foo'}", $a);
}

function spreadOfArrayLiteralWithByRef(int $x, int $y): void
{
	$a = [...[&$x], &$y];
	$a[0] = 'foo';
	$a[1] = 'bar';
	assertType("'foo'", $x);
	assertType("'bar'", $y);
	assertType("array{'foo', 'bar'}", $a);
}

function spreadAfterExplicitKey(int $x): void
{
	$list = [1, 2];
	$a = [5 => 'a', ...$list, &$x];
	assertType("array{5: 'a', 6: 1, 7: 2, 8: int}", $a);
	$a[8] = 'foo';
	assertType("'foo'", $x);
}

/**
 * @param array{0: int, 1?: int} $list
 */
function optionalKeySpread(array $list, int $x): void
{
	$a = [...$list, &$x];
	$x = 5;

	// the reference is at offset 1 or 2, so no offset is narrowed
	assertType('array{0: int, 1: int, 2?: int, ...<int<min, -1>|int<3, max>, 5>}', $a);
}

function unionSpreadWithDifferentSizes(bool $b, int $x): void
{
	$list = $b ? [1, 2] : [1, 2, 3];
	$a = [...$list, &$x];
	$x = 5;
	assertType('array{0: 1|5, 1: 2|5, 2: int, 3?: int, ...<int<min, -1>|int<4, max>, 5>}', $a);
}

function unionSpreadWithSameSize(bool $b, int $x): void
{
	$list = $b ? [1, 2] : [3, 4];
	$a = [...$list, &$x];
	$x = 5;
	assertType('array{1|3, 2|4, 5}', $a);
}

/** @param Generator<int, int> $g */
function generatorSpread(Generator $g, int $x): void
{
	$a = [...$g, &$x];
	$x = 5;
	assertType('non-empty-list<int>', $a);
}

function byRefBeforeSpread(int $x): void
{
	$list = [1, 2];
	$a = [&$x, ...$list];
	$x = 5;
	assertType('array{5, 1, 2}', $a);
}

/** @param list<int> $list */
function spreadInsideNestedArray(array $list, int $x): void
{
	$a = [[...$list, &$x]];
	$x = 5;
	assertType('array{non-empty-list<int>}', $a);
}

function spreadCrossingPhpIntMax(int $x): void
{
	$a = [9223372036854775806 => 1, ...[2, 3], &$x];
	assertType('array{9223372036854775806: 1, 9223372036854775807: 2}', $a);
}
