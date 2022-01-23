<?php

namespace ArrayFilter;

use function PHPStan\Testing\assertType;

/**
 * @param int[] $list1
 * @param int[] $list2
 * @param int[] $list3
 */
function alwaysEvaluatesToFalse(array $list1, array $list2, array $list3): void
{
	$filtered1 = array_filter($list1, static function ($item): bool { return is_string($item); });
	assertType('array{}', $filtered1);

	$filtered2 = array_filter($list2, static function ($item): bool { return is_string($item); }, ARRAY_FILTER_USE_KEY);
	assertType('array<int>', $filtered2); // not supported yet

	$filtered3 = array_filter($list3, static function ($item, $key): bool { return is_string($item) && is_string($key); }, ARRAY_FILTER_USE_BOTH);
	assertType('array<int>', $filtered3); // not supported yet
}

/**
 * @param array<int|string, int|string> $map1
 * @param array<int|string, int|string> $map2
 * @param array<int|string, int|string> $map3
 */
function filtersString(array $map1, array $map2, array $map3): void
{
	$filtered1 = array_filter($map1, static function ($item): bool { return is_string($item); });
	assertType('array<int|string, string>', $filtered1);

	$filtered2 = array_filter($map2, static function ($item): bool { return is_string($item); }, ARRAY_FILTER_USE_KEY);
	assertType('array<int|string, int|string>', $filtered2); // not supported yet

	$filtered3 = array_filter($map3, static function ($item, $key): bool { return is_string($item) && is_string($key); }, ARRAY_FILTER_USE_BOTH);
	assertType('array<int|string, int|string>', $filtered3); // not supported yet
}
