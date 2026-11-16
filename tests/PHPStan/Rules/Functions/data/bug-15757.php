<?php declare(strict_types = 1);

namespace Bug15757;

/**
 * @template K of array-key
 * @template T
 * @template U
 * @param array<K, T> $array
 * @param callable(T, K): U $fn
 * @return array<K, U>
 */
function mapx(array $array, callable $fn): array
{
	$result = [];
	foreach ($array as $key => $value) {
		$result[$key] = $fn($value, $key);
	}
	return $result;
}

function probe(): void
{
	$indexes = range(0, 9);
	$doubleFn = static fn (int $value): int => $value * 2;
	mapx($indexes, static fn (int $index): int => $doubleFn($index));
	mapx($indexes, static fn (int $index): int => $index - $doubleFn(1));
}
