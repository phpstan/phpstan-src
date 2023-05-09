<?php declare(strict_types = 1);

/**
 * @template TKey of array-key
 * @template TValue of mixed
 * @template TResult of mixed
 * @param array<TKey, TValue> $data
 * @param callable(TValue, TKey): TResult $callback
 * @return array<TKey, TResult>
 */
function map(array $data, callable $callback): array {
	$mapped = [];
	foreach ($data as $key => $value) {
		$mapped[$key] = $callback($value, $key);
	}
	return $mapped;
}

map(['test'], strlen(...));
