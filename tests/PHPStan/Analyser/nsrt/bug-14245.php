<?php declare(strict_types = 1);

namespace Bug14245;

use function array_key_first;
use function array_key_last;
use function PHPStan\Testing\assertType;

/**
 * @return list<int>
 */
function foo(): array {
	return [];
}

function doFoo(): void {
	$range = foo();
	$count = count($range);
	assertType('list<int>', $range);
	if ($count > 0) {
		assertType('non-empty-list<int>', $range);
		$range[count($range) - 1] = 37;
		assertType('non-empty-list<int>', $range);
	}

	assertType('list<int>', $range);
}

function doBar(): void {
	$range = foo();
	$count = count($range);
	assertType('list<int>', $range);
	if ($count > 0) {
		assertType('non-empty-list<int>', $range);
		$range[array_key_last($range)] = 37;
		assertType('non-empty-list<int>', $range);
	}

	assertType('list<int>', $range);
}

function doBaz(): void {
	$range = foo();
	$count = count($range);
	assertType('list<int>', $range);
	if ($count > 0) {
		assertType('non-empty-list<int>', $range);
		$range[array_key_first($range)] = 37;
		assertType('non-empty-list<int>', $range);
	}

	assertType('list<int>', $range);
}
