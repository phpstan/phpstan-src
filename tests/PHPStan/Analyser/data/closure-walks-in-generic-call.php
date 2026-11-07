<?php declare(strict_types = 1);

namespace ClosureWalksInGenericCall;

/**
 * @template T
 * @template U
 *
 * @param list<T> $list
 * @param callable(T): U $fn
 *
 * @return list<U>
 */
function mapList(array $list, callable $fn): array
{
	$result = [];
	foreach ($list as $item) {
		$result[] = $fn($item);
	}

	return $result;
}

/**
 * @param list<int> $integers
 * @return list<array{int}>
 */
function wrap(array $integers): array
{
	return mapList($integers, fn ($i) => [$i]);
}

/**
 * @param list<int> $integers
 * @return list<string>
 */
function stringify(array $integers): array
{
	return mapList($integers, function ($i) {
		return (string) $i;
	});
}
