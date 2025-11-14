<?php

namespace ArrayMergeConstNonConst;

use function PHPStan\Testing\assertType;

function doFoo(array $post): void {
	assertType("non-empty-array&hasOffset('a')&hasOffset('b')", array_merge(['a' => 1, 'b' => false, 10 => 99], $post));
}

function doBar(array $array): void {
	assertType("non-empty-array&hasOffsetValue('a', 1)&hasOffsetValue('b', false)", array_merge($array, ['a' => 1, 'b' => false, 10 => 99]));
}

function doFooBar(array $array): void {
	assertType("non-empty-array&hasOffset('x')&hasOffsetValue('a', 1)&hasOffsetValue('b', false)&hasOffsetValue('c', 'e')", array_merge(['c' => 'd', 'x' => 'y'], $array, ['a' => 1, 'b' => false, 'c' => 'e']));
}

function doFooInts(array $array): void {
	assertType("non-empty-array&hasOffsetValue('a', 1)&hasOffsetValue('c', 'e')", array_merge([1 => 'd'], $array, ['a' => 1, 3 => false, 'c' => 'e']));
}

/**
 * @param array<string> $array
 */
function floatKey(array $array): void {
	assertType("non-empty-array<string>&hasOffsetValue('a', '1')&hasOffsetValue('c', 'e')", array_merge([4.23 => 'd'], $array, ['a' => '1', 3 => 'false', 'c' => 'e']));
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
	assertType("non-empty-array&hasOffsetValue('b', 2)", array_merge($arr2, $array));
}

function hasOffsetKeys(array $array, array $arr2): void {
	if (array_key_exists('b', $array)) {
		assertType("non-empty-array&hasOffsetValue('b', mixed)", array_merge($arr2, $array));
	}
}

function hasOffsetValueKeys(array $array, array $arr2): void {
	$array['b'] = 123;

	assertType("non-empty-array&hasOffsetValue('b', 123)", array_merge($arr2, $array));
}

/**
 * @param array{a?: 1, b?: 2} $allOptional
 */
function doAllOptional(array $allOptional, array $arr2): void {
	assertType("array", array_merge($arr2, $allOptional));
	assertType("array", array_merge($allOptional, $arr2));
}
