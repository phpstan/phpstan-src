<?php

namespace Bug14747;

use function array_intersect_key;
use function PHPStan\Testing\assertType;

/** @return array<mixed> */
function get_options(): array {
	return ['foo' => 'bar'];
}

function test(): void {
	$options = get_options();

	$o = array_intersect_key($options, ['a' => null, 'b' => null]);
	assertType('array{a?: mixed, b?: mixed}', $o);
}

/**
 * @param array<int|string, string> $arr
 */
function literalShape(array $arr): void {
	assertType('array{foo?: string}', array_intersect_key($arr, ['foo' => 17]));
	assertType('array{a?: string, b?: string}', array_intersect_key($arr, ['a' => 1, 'b' => 2]));
}

/**
 * @param array<int, string> $arr
 */
function keysOutsideRange(array $arr): void {
	// string keys cannot intersect int-keyed array
	assertType('array{}', array_intersect_key($arr, ['foo' => 17, 'bar' => 18]));
}

/**
 * @param array<int|string, string> $arr
 */
function unionOfShapes(array $arr, bool $b): void {
	$other = $b ? ['a' => 1] : ['b' => 2, 'c' => 3];
	assertType('array{a?: string}|array{b?: string, c?: string}', array_intersect_key($arr, $other));
}

/**
 * @param array<int|string, string> $arr
 * @param array{a: 1, ...<string, mixed>} $unsealed
 */
function unsealedShape(array $arr, array $unsealed): void {
	assertType('array{a?: string, ...<string, string>}', array_intersect_key($arr, $unsealed));
}
