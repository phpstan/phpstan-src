<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug7279;

use function PHPStan\Testing\assertType;

/**
 * @template K of array-key
 * @template T
 * @param array<K, T> $array
 * @param callable(T, K): bool $fn
 * @return ($array is non-empty-array ? K|null : null)
 */
function findKey(array $array, callable $fn): string|int|null
{
	foreach ($array as $key => $value) {
		if ($fn($value, $key)) {
			return $key;
		}
	}

	return null;
}

/**
 * @param callable(mixed): bool $callback
 * @param array<never, never> $emptyList
 * @param array{} $emptyMap
 * @param array<int, string> $unknownList
 * @param array{id?: int, name?: string} $unknownMap
 * @param non-empty-array<int, string> $nonEmptyList
 * @param array{work: string} $nonEmptyMap
 */
function test(callable $callback, array $emptyList, array $emptyMap, array $unknownList, array $unknownMap, array $nonEmptyList, array $nonEmptyMap): void
{
	assertType('null', findKey([], $callback));
	assertType('null', findKey($emptyList, $callback));
	assertType('null', findKey($emptyMap, $callback));
	assertType('int|null', findKey($unknownList, $callback));
	assertType("'id'|'name'|null", findKey($unknownMap, $callback));
	assertType('int|null', findKey($nonEmptyList, $callback));
	assertType("'work'|null", findKey($nonEmptyMap, $callback));
}
