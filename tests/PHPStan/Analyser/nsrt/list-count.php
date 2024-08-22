<?php

namespace ListCount;

use function PHPStan\Testing\assertType;

/**
 * @param list<int> $items
 */
function foo(array $items) {
	assertType('list<int>', $items);
	if (count($items) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items) === 0) {
		assertType('array{}', $items);
	} elseif (count($items) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int> $items
 */
function modeCount(array $items, int $mode) {
	assertType('list<int>', $items);
	if (count($items, $mode) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items, $mode) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, $mode) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function modeCountOnMaybeArray(array $items, int $mode) {
	assertType('list<array<int>|int>', $items);
	if (count($items, $mode) === 3) {
		assertType('non-empty-list<array<int>|int>', $items);
		array_shift($items);
		assertType('list<array<int>|int>', $items);
	} elseif (count($items, $mode) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, $mode) === 5) {
		assertType('non-empty-list<array<int>|int>', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}


/**
 * @param list<int> $items
 */
function normalCount(array $items) {
	assertType('list<int>', $items);
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{int, int, int}', $items);
		array_shift($items);
		assertType('array{int, int}', $items);
	} elseif (count($items, COUNT_NORMAL) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_NORMAL) === 5) {
		assertType('array{int, int, int, int, int}', $items);
	} else {
		assertType('non-empty-list<int>', $items);
	}
	assertType('list<int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function recursiveCountOnMaybeArray(array $items):void {
	assertType('list<array<int>|int>', $items);
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('non-empty-list<array<int>|int>', $items);
		array_shift($items);
		assertType('list<array<int>|int>', $items);
	} elseif (count($items, COUNT_RECURSIVE) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_RECURSIVE) === 5) {
		assertType('non-empty-list<array<int>|int>', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}

/**
 * @param list<int|int[]> $items
 */
function normalCountOnMaybeArray(array $items):void {
	assertType('list<array<int>|int>', $items);
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{array<int>|int, array<int>|int, array<int>|int}', $items);
		array_shift($items);
		assertType('array{array<int>|int, array<int>|int}', $items);
	} elseif (count($items, COUNT_NORMAL) === 0) {
		assertType('array{}', $items);
	} elseif (count($items, COUNT_NORMAL) === 5) {
		assertType('array{array<int>|int, array<int>|int, array<int>|int, array<int>|int, array<int>|int}', $items);
	} else {
		assertType('non-empty-list<array<int>|int>', $items);
	}
	assertType('list<array<int>|int>', $items);
}

class A {}

/**
 * @param list<A> $items
 */
function cannotCountRecursive($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('array{ListCount\A, ListCount\A, ListCount\A}', $items);
	}
}

/**
 * @param list<array<A>> $items
 */
function cannotCountRecursiveNestedArray($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{array<ListCount\A>, array<ListCount\A>, array<ListCount\A>}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{array<ListCount\A>, array<ListCount\A>, array<ListCount\A>}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('non-empty-list<array<ListCount\A>>', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('non-empty-list<array<ListCount\A>>', $items);
	}
}

class CountableFoo implements \Countable
{
	public function count(): int
	{
		return 3;
	}
}

/**
 * @param list<CountableFoo> $items
 */
function cannotCountRecursiveCountable($items, int $mode)
{
	if (count($items) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, COUNT_NORMAL) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, COUNT_RECURSIVE) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
	if (count($items, $mode) === 3) {
		assertType('array{ListCount\CountableFoo, ListCount\CountableFoo, ListCount\CountableFoo}', $items);
	}
}

function countCountable(CountableFoo $x, int $mode)
{
	if (count($x) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, COUNT_NORMAL) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, COUNT_RECURSIVE) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);

	if (count($x, $mode) === 3) {
		assertType('ListCount\CountableFoo', $x);
	} else {
		assertType('ListCount\CountableFoo', $x);
	}
	assertType('ListCount\CountableFoo', $x);
}

class CountWithOptionalKeys
{
	/**
	 * @param array{0: mixed, 1?: string|null} $row
	 */
	protected function testOptionalKeys($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 1) {
			assertType('array{mixed}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{mixed, string|null}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}
	}

	/**
	 * @param array{mixed}|array{0: mixed, 1?: string|null} $row
	 */
	protected function testOptionalKeysInUnion($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 1) {
			assertType('array{mixed}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{mixed, string|null}', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: mixed, 1?: string|null}', $row);
		}
	}

	/**
	 * @param array{string}|array{0: int, 1?: string|null} $row
	 */
	protected function testOptionalKeysInListsOfTaggedUnion($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}

		if (count($row) === 1) {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		} else {
			assertType('array{0: int, 1?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{int, string|null}', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 1?: string|null}|array{string}', $row);
		}
	}

	/**
	 * @param array{string}|array{0: int, 3?: string|null} $row
	 */
	protected function testOptionalKeysInUnionArray($row): void
	{
		if (count($row) === 0) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}

		if (count($row) === 1) {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		} else {
			assertType('array{0: int, 3?: string|null}', $row);
		}

		if (count($row) === 2) {
			assertType('array{0: int, 3?: string|null}', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}

		if (count($row) === 3) {
			assertType('*NEVER*', $row);
		} else {
			assertType('array{0: int, 3?: string|null}|array{string}', $row);
		}
	}

	/**
	 * @param array{string}|list{0: int, 1?: string|null, 2?: int|null, 3?: float|null} $row
	 * @param int<2, 3> $twoOrThree
	 * @param int<2, max> $twoOrMore
	 * @param int<min, 3> $maxThree
	 * @param int<10, 11> $tenOrEleven
	 */
	protected function testOptionalKeysInUnionListWithIntRange($row, $twoOrThree, $twoOrMore, int $maxThree, $tenOrEleven): void
	{
		if (count($row) >= $twoOrThree) {
			assertType('array{0: int, 1: string|null, 2?: int|null}', $row);
		} else {
			assertType('(array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}&list)|array{string}', $row);
		}

		if (count($row) >= $tenOrEleven) {
			assertType('*NEVER*', $row);
		} else {
			assertType('(array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}&list)|array{string}', $row);
		}

		if (count($row) >= $twoOrMore) {
			assertType('array{0: int, 1: string|null, 2?: int|null, 3?: float|null}&list', $row);
		} else {
			assertType('(array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}&list)|array{string}', $row);
		}

		if (count($row) >= $maxThree) {
			assertType('(array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}&list)|array{string}', $row);
		} else {
			assertType('array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}&list', $row);
		}
	}

	/**
	 * @param array{string}|array{0: int, 1?: string|null, 2?: int|null, 3?: float|null} $row
	 * @param int<2, 3> $twoOrThree
	 */
	protected function testOptionalKeysInUnionArrayWithIntRange($row, $twoOrThree): void
	{
		// doesn't narrow because no list
		if (count($row) >= $twoOrThree) {
			assertType('array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}', $row);
		} else {
			assertType('array{0: int, 1?: string|null, 2?: int|null, 3?: float|null}|array{string}', $row);
		}
	}
}
