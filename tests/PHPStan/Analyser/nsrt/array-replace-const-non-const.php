<?php

namespace ArrayReplaceConstNonConst;

use function array_replace;
use function PHPStan\Testing\assertType;

function doFoo(array $post): void {
	assertType(
		"non-empty-array&hasOffset('a')&hasOffset('b')&hasOffset(10)",
		array_replace(['a' => 1, 'b' => false, 10 => 99], $post)
	);
}

function doBar(array $array): void {
	assertType(
		"non-empty-array&hasOffsetValue('a', 1)&hasOffsetValue('b', false)&hasOffsetValue(10, 99)",
		array_replace($array, ['a' => 1, 'b' => false, 10 => 99])
	);
}

function doFooBar(array $array): void {
	assertType(
		"non-empty-array&hasOffset('x')&hasOffsetValue('a', 1)&hasOffsetValue('b', false)&hasOffsetValue('c', 'e')",
		array_replace(['c' => 'd', 'x' => 'y'], $array, ['a' => 1, 'b' => false, 'c' => 'e'])
	);
}
