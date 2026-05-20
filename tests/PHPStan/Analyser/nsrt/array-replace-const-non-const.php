<?php

namespace ArrayReplaceConstNonConst;

use function array_replace;
use function PHPStan\Testing\assertType;

function doFoo(array $post): void {
	assertType(
		'array{a: mixed, b: mixed, 10: mixed, ...}',
		array_replace(['a' => 1, 'b' => false, 10 => 99], $post)
	);
}

function doBar(array $array): void {
	assertType(
		'array{a: 1, b: false, 10: 99, ...}',
		array_replace($array, ['a' => 1, 'b' => false, 10 => 99])
	);
}

function doFooBar(array $array): void {
	assertType(
		"array{c: 'e', x: mixed, a: 1, b: false, ...}",
		array_replace(['c' => 'd', 'x' => 'y'], $array, ['a' => 1, 'b' => false, 'c' => 'e'])
	);
}

/**
 * @param array{a?: 1, b: 2} $array
 */
function doOptShapeKeys(array $array, array $arr2): void {
	assertType('array{b: 2, ...}', array_replace($arr2, $array));
	assertType('array{b: mixed, ...}', array_replace($array, $arr2));
}

function hasOffsetKeys(array $array, array $arr2): void {
	if (array_key_exists('b', $array)) {
		assertType('array{b: mixed, ...}', array_replace($arr2, $array));
		assertType('array{b: mixed, ...}', array_replace($array, $arr2));
	}
}

function maybeHasOffsetKeys(array $array): void {
	$arr2 = [];
	if (rand(0,1)) {
		$arr2 ['ab'] = 'def';
	}

	assertType("array", array_replace($arr2, $array));
	assertType("array", array_replace($array, $arr2));
}

function hasOffsetValueKeys(array $hasB, array $mixedArray, array $hasC): void {
	$hasB['b'] = 123;
	$hasC['c'] = 'def';

	assertType('array{b: 123, ...}', array_replace($mixedArray, $hasB));
	assertType('array{b: mixed, ...}', array_replace($hasB, $mixedArray));

	assertType(
		"array{b: mixed, c: 'def', ...}",
		array_replace($mixedArray, $hasB, $hasC)
	);
	assertType(
		"array{b: mixed, c: 'def', ...}",
		array_replace($hasB, $mixedArray, $hasC)
	);

	assertType(
		'array{c: mixed, b: 123, ...}',
		array_replace($hasC, $mixedArray, $hasB)
	);
	assertType(
		'array{c: mixed, b: mixed, ...}',
		array_replace($hasC, $hasB, $mixedArray)
	);

	if (rand(0, 1)) {
		$hasBorC = ['b' => 1];
	} else {
		$hasBorC = ['c' => 2];
	}
	assertType('array{b: 1}|array{c: 2}', $hasBorC);
	assertType("non-empty-array", array_replace($mixedArray, $hasBorC));
	assertType("non-empty-array", array_replace($hasBorC, $mixedArray));

	if (rand(0, 1)) {
		$differentCs = ['c' => 10];
	} else {
		$differentCs = ['c' => 20];
	}
	assertType('array{c: 10}|array{c: 20}', $differentCs);
	assertType('array{c: 10|20, ...}', array_replace($mixedArray, $differentCs));
	assertType('array{c: mixed, ...}', array_replace($differentCs, $mixedArray));

	assertType('array{c: 10|20, ...}', array_replace($mixedArray, $hasBorC, $differentCs));
	assertType("non-empty-array", array_replace($differentCs, $mixedArray, $hasBorC)); // could be non-empty-array&hasOffset('c')
	assertType('array{c: 10|20, ...}', array_replace($hasBorC, $mixedArray, $differentCs));
	assertType("non-empty-array", array_replace($differentCs, $hasBorC, $mixedArray)); // could be non-empty-array&hasOffset('c')
}

/**
 * @param array{a?: 1, b?: 2} $allOptional
 */
function doAllOptional(array $allOptional, array $arr2): void {
	assertType("array", array_replace($arr2, $allOptional));
	assertType("array", array_replace($allOptional, $arr2));
}

function withArrayReplacement(array $base): void {
	$replacements = [ 'citrus' => [ 'grapefruit' ] ];
	$replacements2 = [ 'citrus' => [ 'kumquat', 'citron' ], 'pome' => [ 'loquat' ] ];

	$basket = array_replace($base, $replacements, $replacements2);
	assertType("array{citrus: array{'kumquat', 'citron'}, pome: array{'loquat'}, ...}", $basket);
}

/**
 * @param array{foo: int, x: string}|array{foo: string, y: 1} $arr1
 */
function doUnions(array $arr1, array $arr2): void {
	assertType('array{foo: mixed, ...}', array_replace($arr1, $arr2));
	assertType('array{foo: int|string, ...}', array_replace($arr2, $arr1));
}
