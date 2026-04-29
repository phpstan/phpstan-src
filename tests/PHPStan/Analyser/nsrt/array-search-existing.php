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
