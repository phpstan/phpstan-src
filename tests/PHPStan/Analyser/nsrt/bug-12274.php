<?php declare(strict_types=1);

namespace Bug12274;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<int> $items
 *
 * @return non-empty-list<int>
 */
function getItems(array $items): array
{
	foreach ($items as $index => $item) {
		$items[$index] = 1;
	}

	assertType('non-empty-list<int>', $items);
	return $items;
}

/**
 * @param non-empty-list<int> $items
 *
 * @return non-empty-list<int>
 */
function getItemsByModifiedIndex(array $items): array
{
	foreach ($items as $index => $item) {
		$index++;

		$items[$index] = 1;
	}

	assertType('non-empty-array<int<0, max>, int>', $items);
	return $items;
}
