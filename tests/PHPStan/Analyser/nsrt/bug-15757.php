<?php declare(strict_types = 1);

namespace Bug15757Nsrt;

use function PHPStan\Testing\assertType;

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

/**
 * @template T
 * @param callable(): T $fn
 * @return T
 */
function call(callable $fn)
{
	return $fn();
}

function probe(): void
{
	$indexes = range(0, 9);
	$doubleFn = static fn (int $value): int => $value * 2;
	assertType('array<0|1|2|3|4|5|6|7|8|9, 0|2|4|6|8|10|12|14|16|18>', mapx($indexes, static fn (int $index): int => $doubleFn($index)));
	assertType('2', $doubleFn(1));
	assertType('2', call(static fn () => $doubleFn(1)));
	assertType('2', call(static function () use ($doubleFn) {
		return $doubleFn(1);
	}));
}
