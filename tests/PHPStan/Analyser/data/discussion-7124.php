<?php declare(strict_types = 1);

namespace Discussion7124;

/**
 * @template K of array-key
 * @template T
 *
 * @param array<K, T> $array
 * @param (
 *     $mode is ARRAY_FILTER_USE_BOTH
 *     ? (callable(T, K=): bool)
 *     : (
 *         $mode is ARRAY_FILTER_USE_KEY
 *         ? (callable(K): bool)
 *         : (
 *             $mode is 0
 *             ? (callable(T): bool)
 *             : null
 *         )
 *     )
 * ) $callback
 * @param ARRAY_FILTER_USE_BOTH|ARRAY_FILTER_USE_KEY|0 $mode
 *
 * @return array<K, T>
 */
function filter(array $array, ?callable $callback = null, int $mode = ARRAY_FILTER_USE_BOTH): array
{
	return null !== $callback
		? array_filter($array, $callback, $mode)
		: array_filter($array);
}

// This one does fail, as both the value + key is asked and the key + value is used
filter(
	[false, true, false],
	static fn (int $key, bool $value): bool => 0 === $key % 2 && $value,
	mode: ARRAY_FILTER_USE_BOTH,
);

// This one should fail, as both the value + key is asked but only the key is used
filter(
	[false, true, false],
	static fn (int $key): bool => 0 === $key % 2,
	mode: ARRAY_FILTER_USE_BOTH,
);

// This one should fail, as only the key is asked but the value is used
filter(
	[false, true, false],
	static fn (bool $value): bool => $value,
	mode: ARRAY_FILTER_USE_KEY,
);

// This one should fail, as only the value is asked but the key is used
filter(
	[false, true, false],
	static fn (int $key): bool => 0 === $key % 2,
	mode: 0,
);
