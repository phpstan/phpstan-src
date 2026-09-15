<?php // lint >= 8.1

declare(strict_types = 1);

namespace ArraySpreadIntStringKeys;

use function PHPStan\Testing\assertType;

/**
 * @param array{a: 1}|array{b: 2} $a
 */
function object2(array $a): void
{
	assertType(
		'array{a: 1}|array{b: 2}',
		[...$a],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 */
function objectAndTuple2(array $a): void
{
	$product = [
		'array{1}',
		'array{a: 1}',
	];

	assertType(
		implode('|', $product),
		[...$a],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 * @param array{c: 3}|array{d: 4} $b
 */
function object4(array $a, array $b): void
{
	assertType(
		'array{a: 1, c: 3}|array{a: 1, d: 4}|array{b: 2, c: 3}|array{b: 2, d: 4}',
		[...$a, ...$b],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 * @param array{2}|array{b: 2} $b
 */
function objectAndTuple4(array $a, array $b): void
{
	$product = [
		'array{0: 1, b: 2}',
		'array{1, 2}',
		'array{a: 1, 0: 2}',
		'array{a: 1, b: 2}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 * @param array{c: 3}|array{d: 4} $b
 * @param array{e: 5}|array{f: 6} $c
 */
function object8(array $a, array $b, array $c): void
{
	$product = [
		'array{a: 1, c: 3, e: 5}',
		'array{a: 1, c: 3, f: 6}',
		'array{a: 1, d: 4, e: 5}',
		'array{a: 1, d: 4, f: 6}',
		'array{b: 2, c: 3, e: 5}',
		'array{b: 2, c: 3, f: 6}',
		'array{b: 2, d: 4, e: 5}',
		'array{b: 2, d: 4, f: 6}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 * @param array{2}|array{b: 2} $b
 * @param array{3}|array{c: 3} $c
 */
function objectAndTuple8(array $a, array $b, array $c): void
{
	$product = [
		'array{0: 1, 1: 2, c: 3}',
		'array{0: 1, b: 2, 1: 3}',
		'array{0: 1, b: 2, c: 3}',
		'array{1, 2, 3}',
		'array{a: 1, 0: 2, 1: 3}',
		'array{a: 1, 0: 2, c: 3}',
		'array{a: 1, b: 2, 0: 3}',
		'array{a: 1, b: 2, c: 3}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 * @param array{c: 3}|array{d: 4} $b
 * @param array{e: 5}|array{f: 6} $c
 * @param array{g: 7}|array{h: 8} $d
 */
function object16(array $a, array $b, array $c, array $d): void
{
	$product = [
		'array{a: 1, c: 3, e: 5, g: 7}',
		'array{a: 1, c: 3, e: 5, h: 8}',
		'array{a: 1, c: 3, f: 6, g: 7}',
		'array{a: 1, c: 3, f: 6, h: 8}',
		'array{a: 1, d: 4, e: 5, g: 7}',
		'array{a: 1, d: 4, e: 5, h: 8}',
		'array{a: 1, d: 4, f: 6, g: 7}',
		'array{a: 1, d: 4, f: 6, h: 8}',
		'array{b: 2, c: 3, e: 5, g: 7}',
		'array{b: 2, c: 3, e: 5, h: 8}',
		'array{b: 2, c: 3, f: 6, g: 7}',
		'array{b: 2, c: 3, f: 6, h: 8}',
		'array{b: 2, d: 4, e: 5, g: 7}',
		'array{b: 2, d: 4, e: 5, h: 8}',
		'array{b: 2, d: 4, f: 6, g: 7}',
		'array{b: 2, d: 4, f: 6, h: 8}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c, ...$d],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 * @param array{2}|array{b: 2} $b
 * @param array{3}|array{c: 3} $c
 * @param array{4}|array{d: 4} $d
 */
function objectAndTuple16(array $a, array $b, array $c, array $d): void
{
	$product = [
		'array{0: 1, 1: 2, 2: 3, d: 4}',
		'array{0: 1, 1: 2, c: 3, 2: 4}',
		'array{0: 1, 1: 2, c: 3, d: 4}',
		'array{0: 1, b: 2, 1: 3, 2: 4}',
		'array{0: 1, b: 2, 1: 3, d: 4}',
		'array{0: 1, b: 2, c: 3, 1: 4}',
		'array{0: 1, b: 2, c: 3, d: 4}',
		'array{1, 2, 3, 4}',
		'array{a: 1, 0: 2, 1: 3, 2: 4}',
		'array{a: 1, 0: 2, 1: 3, d: 4}',
		'array{a: 1, 0: 2, c: 3, 1: 4}',
		'array{a: 1, 0: 2, c: 3, d: 4}',
		'array{a: 1, b: 2, 0: 3, 1: 4}',
		'array{a: 1, b: 2, 0: 3, d: 4}',
		'array{a: 1, b: 2, c: 3, 0: 4}',
		'array{a: 1, b: 2, c: 3, d: 4}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c, ...$d],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 * @param array{c: 3}|array{d: 4} $b
 * @param array{e: 5}|array{f: 6} $c
 * @param array{g: 7}|array{h: 8} $d
 * @param array{i: 9}|array{j: 0} $e
 */
function object32(array $a, array $b, array $c, array $d, array $e): void
{
	$product = [
		'array{a: 1, c: 3, e: 5, g: 7, i: 9}',
		'array{a: 1, c: 3, e: 5, g: 7, j: 0}',
		'array{a: 1, c: 3, e: 5, h: 8, i: 9}',
		'array{a: 1, c: 3, e: 5, h: 8, j: 0}',
		'array{a: 1, c: 3, f: 6, g: 7, i: 9}',
		'array{a: 1, c: 3, f: 6, g: 7, j: 0}',
		'array{a: 1, c: 3, f: 6, h: 8, i: 9}',
		'array{a: 1, c: 3, f: 6, h: 8, j: 0}',
		'array{a: 1, d: 4, e: 5, g: 7, i: 9}',
		'array{a: 1, d: 4, e: 5, g: 7, j: 0}',
		'array{a: 1, d: 4, e: 5, h: 8, i: 9}',
		'array{a: 1, d: 4, e: 5, h: 8, j: 0}',
		'array{a: 1, d: 4, f: 6, g: 7, i: 9}',
		'array{a: 1, d: 4, f: 6, g: 7, j: 0}',
		'array{a: 1, d: 4, f: 6, h: 8, i: 9}',
		'array{a: 1, d: 4, f: 6, h: 8, j: 0}',
		'array{b: 2, c: 3, e: 5, g: 7, i: 9}',
		'array{b: 2, c: 3, e: 5, g: 7, j: 0}',
		'array{b: 2, c: 3, e: 5, h: 8, i: 9}',
		'array{b: 2, c: 3, e: 5, h: 8, j: 0}',
		'array{b: 2, c: 3, f: 6, g: 7, i: 9}',
		'array{b: 2, c: 3, f: 6, g: 7, j: 0}',
		'array{b: 2, c: 3, f: 6, h: 8, i: 9}',
		'array{b: 2, c: 3, f: 6, h: 8, j: 0}',
		'array{b: 2, d: 4, e: 5, g: 7, i: 9}',
		'array{b: 2, d: 4, e: 5, g: 7, j: 0}',
		'array{b: 2, d: 4, e: 5, h: 8, i: 9}',
		'array{b: 2, d: 4, e: 5, h: 8, j: 0}',
		'array{b: 2, d: 4, f: 6, g: 7, i: 9}',
		'array{b: 2, d: 4, f: 6, g: 7, j: 0}',
		'array{b: 2, d: 4, f: 6, h: 8, i: 9}',
		'array{b: 2, d: 4, f: 6, h: 8, j: 0}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c, ...$d, ...$e],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 * @param array{2}|array{b: 2} $b
 * @param array{3}|array{c: 3} $c
 * @param array{4}|array{d: 4} $d
 * @param array{5}|array{e: 5} $e
 */
function objectAndTuple32(array $a, array $b, array $c, array $d, array $e): void
{
	$product = [
		'array{0: 1, 1: 2, 2: 3, 3: 4, e: 5}',
		'array{0: 1, 1: 2, 2: 3, d: 4, 3: 5}',
		'array{0: 1, 1: 2, 2: 3, d: 4, e: 5}',
		'array{0: 1, 1: 2, c: 3, 2: 4, 3: 5}',
		'array{0: 1, 1: 2, c: 3, 2: 4, e: 5}',
		'array{0: 1, 1: 2, c: 3, d: 4, 2: 5}',
		'array{0: 1, 1: 2, c: 3, d: 4, e: 5}',
		'array{0: 1, b: 2, 1: 3, 2: 4, 3: 5}',
		'array{0: 1, b: 2, 1: 3, 2: 4, e: 5}',
		'array{0: 1, b: 2, 1: 3, d: 4, 2: 5}',
		'array{0: 1, b: 2, 1: 3, d: 4, e: 5}',
		'array{0: 1, b: 2, c: 3, 1: 4, 2: 5}',
		'array{0: 1, b: 2, c: 3, 1: 4, e: 5}',
		'array{0: 1, b: 2, c: 3, d: 4, 1: 5}',
		'array{0: 1, b: 2, c: 3, d: 4, e: 5}',
		'array{1, 2, 3, 4, 5}',
		'array{a: 1, 0: 2, 1: 3, 2: 4, 3: 5}',
		'array{a: 1, 0: 2, 1: 3, 2: 4, e: 5}',
		'array{a: 1, 0: 2, 1: 3, d: 4, 2: 5}',
		'array{a: 1, 0: 2, 1: 3, d: 4, e: 5}',
		'array{a: 1, 0: 2, c: 3, 1: 4, 2: 5}',
		'array{a: 1, 0: 2, c: 3, 1: 4, e: 5}',
		'array{a: 1, 0: 2, c: 3, d: 4, 1: 5}',
		'array{a: 1, 0: 2, c: 3, d: 4, e: 5}',
		'array{a: 1, b: 2, 0: 3, 1: 4, 2: 5}',
		'array{a: 1, b: 2, 0: 3, 1: 4, e: 5}',
		'array{a: 1, b: 2, 0: 3, d: 4, 1: 5}',
		'array{a: 1, b: 2, 0: 3, d: 4, e: 5}',
		'array{a: 1, b: 2, c: 3, 0: 4, 1: 5}',
		'array{a: 1, b: 2, c: 3, 0: 4, e: 5}',
		'array{a: 1, b: 2, c: 3, d: 4, 0: 5}',
		'array{a: 1, b: 2, c: 3, d: 4, e: 5}',
	];

	assertType(
		implode('|', $product),
		[...$a, ...$b, ...$c, ...$d, ...$e],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 * @param array{c: 3}|array{d: 4} $b
 * @param array{e: 5}|array{f: 6} $c
 * @param array{g: 7}|array{h: 8} $d
 * @param array{i: 9}|array{j: 0} $e
 * @param array{k: 9}|array{l: 0} $f
 */
function objectInferenceLimits(array $a, array $b, array $c, array $d, array $e, array $f): void
{
	assertType(
		'array{a?: 1, b?: 2, c?: 3, d?: 4, e?: 5, f?: 6, g?: 7, h?: 8, i?: 9, j?: 0, k?: 9, l?: 0}',
		[...$a, ...$b, ...$c, ...$d, ...$e, ...$f],
	);
}

/**
 * @param array{1}|array{a: 1} $a
 * @param array{2}|array{b: 2} $b
 * @param array{3}|array{c: 3} $c
 * @param array{4}|array{d: 4} $d
 * @param array{5}|array{e: 5} $e
 * @param array{6}|array{f: 6} $f
 */
function objectAndTupleInferenceLimits(array $a, array $b, array $c, array $d, array $e, array $f): void
{
	$approximated = 'array{'.implode(', ', [
		'0?: 1|2|3|4|5|6',
		'a?: 1',
		'1?: 2|3|4|5|6',
		'b?: 2',
		'2?: 3|4|5|6',
		'c?: 3',
		'3?: 4|5|6',
		'd?: 4',
		'4?: 5|6',
		'e?: 5',
		'5?: 6',
		'f?: 6',
	]).'}';

	assertType(
		$approximated,
		[...$a, ...$b, ...$c, ...$d, ...$e, ...$f],
	);
}

/**
 * @param array{1}|array{2, 3} $a
 */
function differentTupleLengths(array $a): void
{
	assertType(
		'array{1, 4}|array{2, 3, 4}',
		[...$a, 4],
	);
}

/**
 * @param array{a: 1}|array{b: 2, c: 3} $a
 */
function differentShapeLengths(array $a): void
{
	assertType(
		'array{a: 1, d: 4}|array{b: 2, c: 3, d: 4}',
		[...$a, 'd' => 4],
	);
}

/**
 * @param array{1, 2} | array{c: 3, d: 4} $a
 */
function differentTupleAndShapeLengths(array $a): void
{
	assertType(
		'array{1, 2, 5}|array{c: 3, d: 4, 0: 5}',
		[...$a, 5],
	);
}

/**
 * @param array{int}|array{b: int}|int $input
 */
function notOnlyArrays(int|array $input): void
{
    assertType(
		'array{b: int}|array{int}|int',
		is_int($input) ? $input : [...$input],
	);
}

/**
 * @param array{x: 1}|array{a: 2} $a
 * @param array{x: 3}|array{b: 4} $b
 */
function overlappingKeys(array $a, array $b): void
{
	assertType(
		'array{a: 2, b: 4}|array{a?: 2, x: 3}|array{x: 1, b: 4}',
		[...$a, ...$b],
	);
}

/**
 * @param array{a: 1}|array{b: 2} $a
 */
function explicitKeysAroundSpread(array $a): void
{
	assertType(
		'array{a: 0, b: 2}|array{a: 1}',
		['a' => 0, ...$a],
	);
	assertType(
		'array{b?: 2, a: 0}',
		[...$a, 'a' => 0],
	);
}

/**
 * @param array{a: 1, x?: 3}|array{b: 2} $a
 */
function optionalKeyInUnionMember(array $a): void
{
	assertType(
		'array{a: 1, x?: 3}|array{b: 2}',
		[...$a],
	);
}
