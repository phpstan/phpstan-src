<?php

namespace Bug1940WithKey;

use function PHPStan\Testing\assertType;

/**
 * Reviewer's exact example: by-ref with key, modifying sub-element.
 * This already worked before this PR.
 * @param list<array{one: string}> $a
 */
function byRefWithKeyModifySubElement(array $a): void
{
	foreach ($a as $k => &$testArray) {
		$testArray['two'] = 'two';
	}

	assertType("list<array{one: string, two: 'two'}>", $a);
}

/**
 * By-ref WITHOUT key, modifying sub-element.
 * Parallel case to the reviewer's example but without key variable.
 * @param list<array{one: string}> $a
 */
function byRefWithoutKeyModifySubElement(array $a): void
{
	foreach ($a as &$testArray) {
		$testArray['two'] = 'two';
	}

	assertType("list<array{one: string, two: 'two'}>", $a);
}

/**
 * By-ref with key, direct overwrite (already worked before this PR)
 * @param array<int, string> $arr
 */
function byRefWithKeyDirectOverwrite(array $arr): void
{
	foreach ($arr as $k => &$v) {
		$v = 1;
	}

	assertType('array<int, 1>', $arr);
}

/**
 * By-ref without key, direct overwrite (this PR's main fix)
 * @param array<int, string> $arr
 */
function byRefWithoutKeyDirectOverwrite(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 1;
	}

	assertType('array<int, 1>', $arr);
}
