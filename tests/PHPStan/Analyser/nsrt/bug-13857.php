<?php

declare(strict_types = 1);

namespace Bug13857;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, array{state: string}> $array
 */
function test(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	assertType("non-empty-array<int, array{state: string}>", $array);
}

/**
 * @param array<int, array{state?: string}> $array
 */
function testMaybe(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	assertType("non-empty-array<int, array{state?: string}>", $array);
}

/**
 * @param array<int, array{state: string|bool}> $array
 */
function testUnionValue(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	assertType("non-empty-array<int, array{state: bool|string}>", $array);
}

/**
 * @param array<int, array{state: string}|array{foo: int}> $array
 */
function testUnionArray(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	assertType("non-empty-array<int, non-empty-array{foo?: int, state?: string}>", $array);
}

/**
 * @param array<int, array{state: string}|array{foo: int}> $array
 */
function testUnionArrayDifferentType(array $array, int $id): void {
	$array[$id]['state'] = true;
	assertType("non-empty-array<int, array{state: string}|non-empty-array{foo?: int, state?: true}>", $array);
}
