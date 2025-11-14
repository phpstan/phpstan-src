<?php

namespace ArrayMergeConstNonConst;

use function PHPStan\Testing\assertType;

function doFoo(array $post): void {
	assertType("non-empty-array&hasOffset('a')&hasOffset('b')", array_merge(['a' => 1, 'b' => false], $post));
}

function doBar(array $array): void {
	assertType("non-empty-array&hasOffset('a')&hasOffset('b')", array_merge($array, ['a' => 1, 'b' => false]));
}

function doFooBar(array $array): void {
	assertType("non-empty-array&hasOffset('a')&hasOffset('b')&hasOffset('c')", array_merge(['c' => 'd'], $array, ['a' => 1, 'b' => false, 'c' => 'e']));
}

function doFooInts(array $array): void {
	assertType("non-empty-array&hasOffset('a')&hasOffset('c')&hasOffset(1)&hasOffset(3)", array_merge([1 => 'd'], $array, ['a' => 1, 3 => false, 'c' => 'e']));
}

/**
 * @param array<string> $array
 */
function floatKey(array $array): void {
	assertType("non-empty-array<string>&hasOffset('a')&hasOffset('c')&hasOffset(3)&hasOffset(4)", array_merge([4.23 => 'd'], $array, ['a' => '1', 3 => 'false', 'c' => 'e']));
}

function doOptKeys(array $array, array $arr2): void {
	if (rand(0, 1)) {
		$array['abc'] = 'def';
	}
	assertType("array", array_merge($arr2, $array));
}

/**
 * @param array{a?: 1, b: 2} $array
 */
function doOptShapeKeys(array $array, array $arr2): void {
	assertType("non-empty-array&hasOffset('b')", array_merge($arr2, $array));
}
