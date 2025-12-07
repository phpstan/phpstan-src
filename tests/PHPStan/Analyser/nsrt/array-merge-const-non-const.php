<?php

namespace ArrayMergeConstNonConst;

use function PHPStan\Testing\assertType;

function doFoo(array $post): void {
	assertType(
		"non-empty-array&hasOffset('a')&hasOffset('b')",
		array_merge(['a' => 1, 'b' => false, 10 => 99], $post)
	);
}

function doBar(array $array): void {
	assertType(
		"non-empty-array&hasOffsetValue('a', 1)&hasOffsetValue('b', false)",
		array_merge($array, ['a' => 1, 'b' => false, 10 => 99])
	);
}

function doFooBar(array $array): void {
	assertType(
		"non-empty-array&hasOffset('x')&hasOffsetValue('a', 1)&hasOffsetValue('b', false)&hasOffsetValue('c', 'e')",
		array_merge(['c' => 'd', 'x' => 'y'], $array, ['a' => 1, 'b' => false, 'c' => 'e'])
	);
}

function doFooInts(array $array): void {
	assertType(
		"non-empty-array&hasOffsetValue('a', 1)&hasOffsetValue('c', 'e')",
		array_merge([1 => 'd'], $array, ['a' => 1, 3 => false, 'c' => 'e'])
	);
}

/**
 * @param array<string> $array
 */
function floatKey(array $array): void {
	assertType(
		"non-empty-array<string>&hasOffsetValue('a', '1')&hasOffsetValue('c', 'e')",
		array_merge([4.23 => 'd'], $array, ['a' => '1', 3 => 'false', 'c' => 'e'])
	);
}

function doOptKeys(array $array, array $arr2): void {
	if (rand(0, 1)) {
		$array['abc'] = 'def';
	}
	assertType("array", array_merge($arr2, $array));
	assertType("array", array_merge($array, $arr2));
}

/**
 * @param array{a?: 1, b: 2} $array
 */
function doOptShapeKeys(array $array, array $arr2): void {
	assertType("non-empty-array&hasOffsetValue('b', 2)", array_merge($arr2, $array));
	assertType("non-empty-array&hasOffset('b')", array_merge($array, $arr2));
}

function hasOffsetKeys(array $array, array $arr2): void {
	if (array_key_exists('b', $array)) {
		assertType("non-empty-array&hasOffsetValue('b', mixed)", array_merge($arr2, $array));
		assertType("non-empty-array&hasOffset('b')", array_merge($array, $arr2));
	}
}

function maybeHasOffsetKeys(array $array): void {
	$arr2 = [];
	if (rand(0,1)) {
		$arr2 ['ab'] = 'def';
	}

	assertType("array", array_merge($arr2, $array));
	assertType("array", array_merge($array, $arr2));
}

function hasOffsetValueKeys(array $hasB, array $mixedArray, array $hasC): void {
	$hasB['b'] = 123;
	$hasC['c'] = 'def';

	assertType("non-empty-array&hasOffsetValue('b', 123)", array_merge($mixedArray, $hasB));
	assertType("non-empty-array&hasOffset('b')", array_merge($hasB, $mixedArray));

	assertType(
		"non-empty-array&hasOffset('b')&hasOffsetValue('c', 'def')",
		array_merge($mixedArray, $hasB, $hasC)
	);
	assertType(
		"non-empty-array&hasOffset('b')&hasOffsetValue('c', 'def')",
		array_merge($hasB, $mixedArray, $hasC)
	);

	assertType(
		"non-empty-array&hasOffset('c')&hasOffsetValue('b', 123)",
		array_merge($hasC, $mixedArray, $hasB)
	);
	assertType(
		"non-empty-array&hasOffset('b')&hasOffset('c')",
		array_merge($hasC, $hasB, $mixedArray)
	);

	if (rand(0, 1)) {
		$hasBorC = ['b' => 1];
	} else {
		$hasBorC = ['c' => 2];
	}
	assertType('array{b: 1}|array{c: 2}', $hasBorC);
	assertType("non-empty-array", array_merge($mixedArray, $hasBorC));
	assertType("non-empty-array", array_merge($hasBorC, $mixedArray));

	if (rand(0, 1)) {
		$differentCs = ['c' => 10];
	} else {
		$differentCs = ['c' => 20];
	}
	assertType('array{c: 10}|array{c: 20}', $differentCs);
	assertType("non-empty-array&hasOffsetValue('c', 10|20)", array_merge($mixedArray, $differentCs));
	assertType("non-empty-array&hasOffset('c')", array_merge($differentCs, $mixedArray));

	assertType("non-empty-array&hasOffsetValue('c', 10|20)", array_merge($mixedArray, $hasBorC, $differentCs));
	assertType("non-empty-array", array_merge($differentCs, $mixedArray, $hasBorC)); // could be non-empty-array&hasOffset('c')
	assertType("non-empty-array&hasOffsetValue('c', 10|20)", array_merge($hasBorC, $mixedArray, $differentCs));
	assertType("non-empty-array", array_merge($differentCs, $hasBorC, $mixedArray)); // could be non-empty-array&hasOffset('c')
}

/**
 * @param array{a?: 1, b?: 2} $allOptional
 */
function doAllOptional(array $allOptional, array $arr2): void {
	assertType("array", array_merge($arr2, $allOptional));
	assertType("array", array_merge($allOptional, $arr2));
}
