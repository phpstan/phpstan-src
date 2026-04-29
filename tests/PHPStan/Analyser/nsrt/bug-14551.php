<?php declare(strict_types = 1);

namespace Bug14551;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<'a'|'b'> $keys
 */
function foreachTwoKeys(array $keys): void
{
	$result = [];
	foreach ($keys as $k) {
		$result[$k]['x'] = 1;
		$result[$k]['y'] = 2;
	}

	assertType("non-empty-array{a?: array{x: 1, y: 2}, b?: array{x: 1, y: 2}}", $result);
}

/**
 * @param non-empty-list<'a'|'b'> $keys
 */
function foreachThreeKeys(array $keys): void
{
	$result = [];
	foreach ($keys as $k) {
		$result[$k]['x'] = 1;
		$result[$k]['y'] = 2;
		$result[$k]['z'] = 3;
	}

	assertType("non-empty-array{a?: array{x: 1, y: 2, z: 3}, b?: array{x: 1, y: 2, z: 3}}", $result);
}

/**
 * Test without foreach: union-key nested assignment
 * @param 'a'|'b' $k
 */
function withoutForeach(string $k): void
{
	$result = [];
	$result[$k]['x'] = 1;
	$result[$k]['y'] = 2;

	assertType("non-empty-array{a?: array{x: 1, y: 2}, b?: array{x: 1, y: 2}}", $result);
}

/**
 * Test with integer union keys
 * @param 0|1 $k
 */
function integerKeys(int $k): void
{
	$result = [];
	$result[$k]['x'] = 1;
	$result[$k]['y'] = 2;

	assertType("non-empty-array{0?: array{x: 1, y: 2}, 1?: array{x: 1, y: 2}}", $result);
}

/**
 * Test three-way union key
 * @param 'a'|'b'|'c' $k
 */
function threeWayUnion(string $k): void
{
	$result = [];
	$result[$k]['x'] = 1;
	$result[$k]['y'] = 2;

	assertType("non-empty-array{a?: array{x: 1, y: 2}, b?: array{x: 1, y: 2}, c?: array{x: 1, y: 2}}", $result);
}

/**
 * Required keys should still union (not replace) — existing behavior must be preserved
 */
function requiredKeysStillUnion(): void
{
	$result = ['a' => ['x' => 1], 'b' => ['x' => 1]];
	/** @var 'a'|'b' $k */
	$k = 'a';
	$result[$k]['y'] = 2;

	assertType("array{a: array{x: 1, y?: 2}, b: array{x: 1, y?: 2}}", $result);
}

/**
 * Non-nested union-key overwrites with optional keys
 * @param 'a'|'b' $k
 */
function nonNested(string $k): void
{
	$result = [];
	$result[$k] = 1;
	$result[$k] = 2;

	assertType("non-empty-array{a?: 2, b?: 2}", $result);
}
