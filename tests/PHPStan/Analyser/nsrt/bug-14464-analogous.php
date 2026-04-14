<?php declare(strict_types = 1);

namespace Bug14464Analogous;

use function PHPStan\Testing\assertType;

/**
 * sizeof() alias
 * @param list<int> $items
 */
function testSizeof(array $items): void {
	$count = sizeof($items);
	if ($count === 3) {
		assertType('array{int, int, int}', $items);
	}
}

/**
 * Inline count still works
 * @param list<int> $items
 */
function testInlineCount(array $items): void {
	if (count($items) === 3) {
		assertType('array{int, int, int}', $items);
	}
}

/**
 * explode() result
 */
function testExplode(string $input): void {
	$parts = explode(',', $input);
	$count = count($parts);
	if ($count === 3) {
		assertType('array{string, string, string}', $parts);
	} elseif ($count === 1) {
		assertType('array{string}', $parts);
	}
}

/**
 * Variable count >= N (range comparison)
 * @param list<int> $items
 */
function testGreaterOrEqual(array $items): void {
	$count = count($items);
	if ($count >= 3) {
		assertType('non-empty-list<int>', $items);
	}
}

/**
 * Count value > 8 (beyond pre-computed limit)
 * @param list<int> $items
 */
function testBeyondLimit(array $items): void {
	$count = count($items);
	if ($count === 10) {
		assertType('non-empty-list<int>', $items);
	}
}

/**
 * Count with mode argument excluded from pre-computation
 * @param list<int> $items
 */
function testCountWithMode(array $items, int $mode): void {
	$count = count($items, $mode);
	if ($count === 3) {
		assertType('non-empty-list<int>', $items);
	}
}

/**
 * Variable count on non-empty-list
 * @param non-empty-list<string> $items
 */
function testNonEmptyList(array $items): void {
	$count = count($items);
	if ($count === 2) {
		assertType('array{string, string}', $items);
	}
}

/**
 * Variable count with switch statement
 * @param list<int> $items
 */
function testSwitch(array $items): void {
	$count = count($items);
	switch ($count) {
		case 1:
			assertType('array{int}', $items);
			break;
		case 2:
			assertType('array{int, int}', $items);
			break;
		case 3:
			assertType('array{int, int, int}', $items);
			break;
	}
}
