<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11861;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template K of array-key
 * @template R
 *
 * @param array<K, T> $source
 * @param callable(T, K): R $mappingFunction
 * @return array<K, R>
 */
function mapArray(array $source, callable $mappingFunction): array
{
	$result = [];

	foreach ($source as $key => $value) {
		$result[$key] = $mappingFunction($value, $key);
	}

	return $result;
}

/**
 * @template K
 * @template T
 *
 * @param array<K, null|T> $source
 * @return array<K, T>
 */
function filterArrayNotNull(array $source): array
{
	return array_filter(
		$source,
		fn($item) => $item !== null,
		ARRAY_FILTER_USE_BOTH
	);
}

/** @var list<array<string, null|int>> $a */
$a = [];

$mappedA = mapArray(
	$a,
	static fn(array $entry) => filterArrayNotNull($entry)
);

$mappedAWithFirstClassSyntax = mapArray(
	$a,
	filterArrayNotNull(...)
);

assertType('array<int<0, max>, array<int|string, mixed>>', $mappedA);
assertType('array<int<0, max>, array<int|string, mixed>>', $mappedAWithFirstClassSyntax);
