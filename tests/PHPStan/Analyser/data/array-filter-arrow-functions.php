<?php // onlyif PHP_VERSION_ID >= 70400

namespace ArrayFilterArrowFunctions;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, int> $list1
 * @param array<int, int> $list2
 * @param array<int, int> $list3
 */
function alwaysEvaluatesToFalse(array $list1, array $list2, array $list3): void
{
	$filtered1 = array_filter($list1, static fn($item): bool => is_string($item));
	assertType('array{}', $filtered1);

	$filtered2 = array_filter($list2, static fn($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
	assertType('array{}', $filtered2);

	$filtered3 = array_filter($list3, static fn($item, $key): bool => is_string($item) && is_string($key), ARRAY_FILTER_USE_BOTH);
	assertType('array{}', $filtered3);
}

/**
 * @param array<int|string, int|string> $map1
 * @param array<int|string, int|string> $map2
 * @param array<int|string, int|string> $map3
 * @param array<int|string, int|string> $map4
 */
function filtersString(array $map1, array $map2, array $map3, array $map4): void
{
	$filtered1 = array_filter($map1, static fn($item): bool => is_string($item));
	assertType('array<int|string, string>', $filtered1);

	$filtered2 = array_filter($map2, static fn($key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
	assertType('array<string, int|string>', $filtered2);

	$filtered3 = array_filter($map3, static fn($item, $key): bool => is_string($item) && is_string($key), ARRAY_FILTER_USE_BOTH);
	assertType('array<string, string>', $filtered3);

	$filtered4 = array_filter($map4, static fn($item): bool => is_string($item), ARRAY_FILTER_USE_BOTH);
	assertType('array<int|string, string>', $filtered4);
}
