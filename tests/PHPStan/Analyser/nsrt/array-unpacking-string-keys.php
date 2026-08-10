<?php // lint >= 8.1

namespace ArrayUnpackingWithStringKeys;

use function PHPStan\Testing\assertType;

$foo = ['a' => 0, ...['a' => 1], ...['b' => 2]];

assertType('array{a: 1, b: 2}', $foo);

$bar = [1, ...['a' => 1], ...['b' => 2]];

assertType('array{0: 1, a: 1, b: 2}', $bar);

// integer keys are renumbered even when the unpacked array also has string keys
$mixed = [9, ...['a' => 1, 5], ...[6, 'b' => 2]];

assertType('array{0: 9, a: 1, 1: 5, 2: 6, b: 2}', $mixed);

$mixedOnlyIntegerKeys = [9, ...[5, 6]];

assertType('array{9, 5, 6}', $mixedOnlyIntegerKeys);

$mixedIntegerKeysOverwritten = [9, ...[3 => 'x']];

assertType("array{9, 'x'}", $mixedIntegerKeysOverwritten);

/**
 * @param array<string, int> $a
 * @param array<int, int> $b
 */
function foo(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('array<int|string, int>', $c);
}

/**
 * @param array<array-key, int> $a
 * @param array<int, int> $b
 */
function bar(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('array<int>', $c);
}

/**
 * @param array<string, int> $a
 * @param array<string, int> $b
 */
function baz(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('array<string, int>', $c);
}

/**
 * @param non-empty-array<string, int> $a
 * @param array<int, int> $b
 */
function nonEmptyArray1(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('non-empty-array<int|string, int>', $c);
}

/**
 * @param array<string, int> $a
 * @param non-empty-array<int, int> $b
 */
function nonEmptyArray2(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('non-empty-array<int|string, int>', $c);
}
