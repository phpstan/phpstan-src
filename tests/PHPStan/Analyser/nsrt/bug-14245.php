<?php declare(strict_types = 1);

namespace Bug14245;

use function array_key_exists;
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
		// we don't know the $list length,
		// therefore count() - N might be before the first element -> degrade to array
		$list[count($list) - 5] = 37;
		assertType('non-empty-array<int<-4, max>, int>', $list);
	}

	assertType('array<int<-4, max>, int>', $list);
}

function listKnownSize(): void {
	$list = foo();
	assertType('list<int>', $list);
	if (count($list) === 5) {
		assertType('array{int, int, int, int, int}', $list);
		$list[count($list) - 3] = 37;
		assertType('array{int, int, 37, int, int}', $list);
	}

	assertType('list<int>', $list);
}

function listKnownHugeSize(): void {
	$list = foo();
	assertType('list<int>', $list);
	if (count($list) === 50000) {
		assertType('non-empty-list<int>', $list);
		$list[count($list) - 3000] = 37;
		assertType('non-empty-array<int<-2999, max>, int>', $list);
	}

	assertType('array<int<-2999, max>, int>', $list);
}

function overwriteKeyLast(): void {
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

function overwriteKeyFirst(): void {
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

function overwriteKeyFirstMaybeEmptyArray(): void {
	$list = foo();
	assertType('list<int>', $list);
	// empty list might return NULL for array_key_first()
	$list[array_key_first($list)] = 37;
	assertType('non-empty-list<int>', $list);
}

function keyDifferentArray(array $arr): void {
	$list = foo();
	assertType('list<int>', $list);
	$list[array_key_first($arr)] = 37;
	assertType('non-empty-array<int>', $list);
}

function overwriteArraySearch($needle): void {
	$list = foo();

	assertType('list<int>', $list);
	// search in empty-array, or with a non-existent key will return false,
	// which gets auto-casted to 0, so we still have a list
	// https://3v4l.org/RZbOK
	$list[array_search($needle, $list)] = 37;
	assertType('non-empty-list<int>', $list);
}

function overwriteArraySearchStrict($needle): void {
	$list = foo();

	assertType('list<int>', $list);
	// search in empty-array, or with a non-existent key will return false,
	// which gets auto-casted to 0, so we still have a list
	// https://3v4l.org/RZbOK
	$list[array_search($needle, $list, true)] = 37;
	assertType('non-empty-list<int>', $list);
}

function ArraySearchWithDifferentArray($array2, $needle): void {
	$list = foo();

	assertType('list<int>', $list);
	$list[array_search($needle, $array2, true)] = 37;
	assertType('non-empty-array<int|string, int>', $list);
}

function ArrayKeyExistsKeepsList($needle): void {
	$list = foo();

	assertType('list<int>', $list);
	if (array_key_exists($needle, $list)) {
		$list[$needle] = 37;
	}
	assertType('list<int>', $list);
}
