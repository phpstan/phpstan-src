<?php // lint >= 8.4

declare(strict_types=1);

namespace Bug14537;

/**
 * @param list<string> $list
 */
function arraySearchNotFalse(array $list, string $s): void
{
	$key = array_search($s, $list);
	if ($key !== false) {
		echo $list[$key];
	}
}

/**
 * @param array<string, int> $map
 */
function arraySearchStringKey(array $map, int $needle): void
{
	$key = array_search($needle, $map);
	if ($key !== false) {
		echo $map[$key];
	}
}

/**
 * @param list<string> $list
 */
function arraySearchReversedComparison(array $list, string $s): void
{
	$key = array_search($s, $list);
	if (false !== $key) {
		echo $list[$key];
	}
}

/**
 * @param list<string> $list
 */
function arrayFindKeyNotNull(array $list, string $s): void
{
	$key = array_find_key($list, fn (string $v) => $v === $s);
	if ($key !== null) {
		echo $list[$key];
	}
}

/**
 * @param array<string, int> $map
 */
function arrayFindKeyStringKey(array $map): void
{
	$key = array_find_key($map, fn (int $v) => $v > 10);
	if ($key !== null) {
		echo $map[$key];
	}
}

/**
 * @param list<string> $list
 */
function arrayFindKeyReversedComparison(array $list, string $s): void
{
	$key = array_find_key($list, fn (string $v) => $v === $s);
	if (null !== $key) {
		echo $list[$key];
	}
}
