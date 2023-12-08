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
function modeCountOnArray(array $items, int $mode) {
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
function recursiveCount(array $items):void {
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
