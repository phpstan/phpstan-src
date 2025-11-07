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

	assertType('non-empty-list<1>', $items);
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

/** @param list<int> $list */
function testKeepListAfterIssetIndex(array $list, int $i): void
{
	if (isset($list[$i])) {
		assertType('list<int>', $list);
		$list[$i] = 21;
		assertType('non-empty-list<int>', $list);
		$list[$i+1] = 21;
		assertType('non-empty-list<int>', $list);
	}
	assertType('list<int>', $list);
}

/** @param list<list<int>> $nestedList */
function testKeepNestedListAfterIssetIndex(array $nestedList, int $i, int $j): void
{
	if (isset($nestedList[$i][$j])) {
		assertType('list<list<int>>', $nestedList);
		assertType('list<int>', $nestedList[$i]);
		$nestedList[$i][$j] = 21;
		assertType('non-empty-list<list<int>>', $nestedList);
		assertType('list<int>', $nestedList[$i]);
	}
	assertType('list<list<int>>', $nestedList);
}

/** @param list<int> $list */
function testKeepListAfterIssetIndexPlusOne(array $list, int $i): void
{
	if (isset($list[$i])) {
		assertType('list<int>', $list);
		$list[$i+1] = 21;
		assertType('non-empty-list<int>', $list);
	}
	assertType('list<int>', $list);
}

/** @param list<int> $list */
function testKeepListAfterIssetIndexOnePlus(array $list, int $i): void
{
	if (isset($list[$i])) {
		assertType('list<int>', $list);
		$list[1+$i] = 21;
		assertType('non-empty-list<int>', $list);
	}
	assertType('list<int>', $list);
}

/** @param list<int> $list */
function testShouldLooseListbyAst(array $list, int $i): void
{
	if (isset($list[$i])) {
		$i++;

		assertType('list<int>', $list);
		$list[1+$i] = 21;
		assertType('non-empty-array<int<0, max>, int>', $list);
	}
	assertType('array<int<0, max>, int>', $list);
}

/** @param list<int> $list */
function testShouldLooseListbyAst2(array $list, int $i): void
{
	if (isset($list[$i])) {
		assertType('list<int>', $list);
		$list[2+$i] = 21;
		assertType('non-empty-array<int<0, max>, int>', $list);
	}
	assertType('array<int<0, max>, int>', $list);
}
