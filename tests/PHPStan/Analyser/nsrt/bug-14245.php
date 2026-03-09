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
	$list = foo();
	$count = count($list);
	assertType('list<int>', $list);
	if ($count > 0) {
		assertType('non-empty-list<int>', $list);
		$list[count($list) - 1] = 37;
		assertType('non-empty-list<int>', $list);
	}

	assertType('list<int>', $list);
}

function doFoo2(): void {
	$list = foo();
	$count = count($list);
	assertType('list<int>', $list);
	if ($count > 0) {
		assertType('non-empty-list<int>', $list);
		$list[count($list) - 5] = 37; // we don't know the $list length, therefore count() - N might be before the first element
		assertType('array<int>', $list);
	}

	assertType('list<int>', $list);
}

function doBar(): void {
	$list = foo();
	$count = count($list);
	assertType('list<int>', $list);
	if ($count > 0) {
		assertType('non-empty-list<int>', $list);
		$list[array_key_last($list)] = 37;
		assertType('non-empty-list<int>', $list);
	}

	assertType('list<int>', $list);
}

function doBaz(): void {
	$list = foo();
	$count = count($list);
	assertType('list<int>', $list);
	if ($count > 0) {
		assertType('non-empty-list<int>', $list);
		$list[array_key_first($list)] = 37;
		assertType('non-empty-list<int>', $list);
	}

	assertType('list<int>', $list);
}
