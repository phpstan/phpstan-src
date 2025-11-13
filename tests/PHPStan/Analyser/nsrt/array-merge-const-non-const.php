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
