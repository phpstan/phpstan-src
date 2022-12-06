<?php declare(strict_types = 1);

namespace Bug7279;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Timeline {}
class Percentage {}

/**
 * @template K of array-key
 * @template T
 *
 * @param array<K, T> $array
 * @param (callable(T, K): bool) $fn
 *
 * @return ($array is non-empty-array ? T|null : null)
 */
function find(array $array, callable $fn): mixed
{
	foreach ($array as $key => $value) {
		if ($fn($value, $key)) {
			return $value;
		}
	}

	return null;
}

/**
 * @template K of array-key
 * @template T
 *
 * @param array<K, T> $array
 * @param (callable(T, K): bool) $fn
 *
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
 * @param non-empty-array<int, Timeline<Percentage>> $nonEmptyList
 * @param array{work: Timeline<Percentage>} $nonEmptyMap
 */
function doFoo(callable $callback, array $emptyList, array $emptyMap, array $unknownList, array $unknownMap, array $nonEmptyList, array $nonEmptyMap): void
{
	// Everything works great for find()

	assertType('null', find([], $callback));
	assertType('null', find($emptyList, $callback));
	assertType('null', find($emptyMap, $callback));

	assertType('string|null', find($unknownList, $callback));
	assertType('int|string|null', find($unknownMap, $callback));

	assertType('Bug7279\Timeline<Bug7279\Percentage>|null', find($nonEmptyList, $callback));
	assertType('Bug7279\Timeline<Bug7279\Percentage>|null', find($nonEmptyMap, $callback));

	// But everything goes to hell for findKey() ?!?

	assertType('null', findKey([], $callback));
	assertType('null', findKey($emptyList, $callback));
	assertType('null', findKey($emptyMap, $callback));

	assertType('int|null', findKey($unknownList, $callback));
	assertType("'id'|'name'|null", findKey($unknownMap, $callback));

	assertType('int|null', findKey($nonEmptyList, $callback));
	assertType("'work'|null", findKey($nonEmptyMap, $callback));
}
