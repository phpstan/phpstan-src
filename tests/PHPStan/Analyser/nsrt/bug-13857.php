<?php declare(strict_types = 1);

namespace Bug13857;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, array{state: string}> $array
 */
function test(array $array, int $id): void {
	$array[$id]['state'] = 'foo';
	// only one element was set to 'foo', not all of them.
	// correct type would be: non-empty-array<int, array{state: string}>
	assertType('non-empty-array<int, array{state: string}>', $array);
}

/**
 * @param array<string, array{name: string, age: int}> $people
 */
function test2(array $people, string $key): void {
	$people[$key]['name'] = 'John';
	// age becomes optional because $key might not exist yet, creating array{name: 'John'} without age
	assertType('non-empty-array<string, array{name: string, age?: int}>', $people);
}
