<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug9652;

/**
 * @template K
 * @template T of int|string
 * @template V
 *
 * @param array<K, V> $source
 * @param (callable(K, V): T) $mappingFunction
 * @return array<T, V>
 */
function mapKeys(array $source, callable $mappingFunction): array
{
	$mappedArray = [];

	foreach ($source as $key => $value) {
		$mappedArray[$mappingFunction($key, $value)] = $value;
	}

	return $mappedArray;
}

/** @var array<array{foo: int, bar: string}> $array */
$array = [];

$array = mapKeys(
	$array,
	fn(int|string $key, array $entry) => $entry['foo']
);
