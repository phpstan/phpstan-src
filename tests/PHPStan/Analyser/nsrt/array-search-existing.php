<?php declare(strict_types=1);

namespace ArraySearchExisting;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $list
 */
function arraySearchNotFalse(array $list, string $s): void
{
	$key = array_search($s, $list);
	if ($key !== false) {
		assertType('non-empty-list<string>', $list);
		assertType('string', $list[$key]);
	}
}

/**
 * @param array<string, int> $map
 */
function arraySearchStringKey(array $map, int $needle): void
{
	$key = array_search($needle, $map);
	if ($key !== false) {
		assertType('int', $map[$key]);
	}
}

/**
 * @param list<string> $list
 */
function arraySearchReversedComparison(array $list, string $s): void
{
	$key = array_search($s, $list);
	if (false !== $key) {
		assertType('string', $list[$key]);
	}
}

/**
 * @param array<string, int|string> $arr
 */
function arraySearchStrictNarrowsToNeedle(array $arr, int $needle): void
{
	$key = array_search($needle, $arr, true);
	if ($key !== false) {
		assertType('non-empty-array<string, int|string>', $arr);
		assertType('(int|string)', $key);
		assertType('int', $arr[$key]);
	} else {
		assertType('array<string, int|string>', $arr);
		assertType('false', $key);
		assertType('*ERROR*', $arr[$key]);
	}
	assertType('array<string, int|string>', $arr);
	assertType('int|string|false', $key);
	assertType('int|string', $arr[$key]);

}

/**
 * @param array<string, int|string> $arr
 */
function arraySearchLooseKeepsValueType(array $arr, int $needle): void
{
	$key = array_search($needle, $arr);
	if ($key !== false) {
		assertType('int|string', $arr[$key]);
	}
}

/**
 * @param array<string, int|string> $arr
 */
function arraySearchStrictInlineAssign(array $arr, int $needle): void
{
	if (($key = array_search($needle, $arr, true)) !== false) {
		assertType('int', $arr[$key]);
	}
}
