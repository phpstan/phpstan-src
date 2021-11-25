<?php

namespace ArrayUnpackingWithStringKeys;

use function PHPStan\Testing\assertType;

$foo = ['a' => 0, ...['a' => 1], ...['b' => 2]];

assertType('array{a: 1, b: 2}', $foo);

$bar = [1, ...['a' => 1], ...['b' => 2]];

assertType('array{0: 1, a: 1, b: 2}', $bar);

/**
 * @param array<string, int> $a
 * @param array<int, int> $b
 */
function foo(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('non-empty-array<int|string, int>', $c);
}

/**
 * @param array<array-key, int> $a
 * @param array<int, int> $b
 */
function bar(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('non-empty-array<int>', $c);
}

/**
 * @param array<string, int> $a
 * @param array<string, int> $b
 */
function baz(array $a, array $b)
{
	$c = [...$a, ...$b];

	assertType('non-empty-array<string, int>', $c);
}
