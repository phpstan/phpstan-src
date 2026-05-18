<?php // lint >= 8.4

declare(strict_types=1);

namespace ArrayFindKeyExisting;

use function PHPStan\Testing\assertType;

/**
 * @param list<string> $list
 */
function arrayFindKeyNotNull(array $list, string $s): void
{
	$key = array_find_key($list, fn (string $v) => $v === $s);
	if ($key !== null) {
		assertType('non-empty-list<string>', $list);
		assertType('int<0, max>', $key);
		assertType('string', $list[$key]);
	} else {
		assertType('list<string>', $list);
		assertType('null', $key);
		assertType('*ERROR*', $list[$key]);
	}
	assertType('list<string>', $list);
	assertType('int<0, max>|null', $key);
	assertType('string', $list[$key]);
}

/**
 * @param array<string, int> $map
 */
function arrayFindKeyStringKey(array $map): void
{
	$key = array_find_key($map, fn (int $v) => $v > 10);
	if ($key !== null) {
		assertType('int', $map[$key]);
	}
}

/**
 * @param list<string> $list
 */
function arrayFindKeyReversedComparison(array $list, string $s): void
{
	$key = array_find_key($list, fn (string $v) => $v === $s);
	if (null !== $key) {
		assertType('string', $list[$key]);
	}
}
