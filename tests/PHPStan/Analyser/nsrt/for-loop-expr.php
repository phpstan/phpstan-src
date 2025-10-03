<?php declare(strict_types=1);

namespace Bug12807;

use function PHPStan\debugScope;
use function PHPStan\Testing\assertType;

/**
 * @param non-empty-list<int> $items
 *
 * @return non-empty-list<int>
 */
function getItemsWithForLoop(array $items): array
{
	for ($i = 0; $i < count($items); $i++) {
		$items[$i] = 1;
	}

	assertType('non-empty-list<int>', $items);
	return $items;
}

/**
 * @param list<string> $items
 *
 * @return list<string>
 */
function getItemsWithForLoopInvertLastCond(array $items): array
{
	for ($i = 0; count($items) > $i; ++$i) {
		$items[$i] = 'hello';
	}

	assertType('list<string>', $items);
	return $items;
}


/**
 * @param array<string> $items
 *
 * @return array<string>
 */
function getItemsArray(array $items): array
{
	for ($i = 0; count($items) > $i; ++$i) {
		$items[$i] = 'hello';
	}

	assertType('array<string>', $items);
	return $items;
}
