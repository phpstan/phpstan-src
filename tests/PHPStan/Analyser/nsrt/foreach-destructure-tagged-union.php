<?php

namespace ForeachDestructureTaggedUnion;

use function PHPStan\Testing\assertType;

class A {}
class B {}

/**
 * @param list<array{null, int}|array{int, null}> $list
 */
function basicTwoVariants(array $list): void
{
	foreach ($list as [$x, $y]) {
		assertType('int|null', $x);
		assertType('int|null', $y);

		if ($x === null) {
			assertType('null', $x);
			assertType('int', $y);
		}
		if ($x !== null) {
			assertType('int', $x);
			assertType('null', $y);
		}
		if ($y === null) {
			assertType('int', $x);
			assertType('null', $y);
		}
		if ($y !== null) {
			assertType('null', $x);
			assertType('int', $y);
		}
	}
}

/**
 * @param list<array{A, int}|array{B, string}> $list
 */
function classDiscriminator(array $list): void
{
	foreach ($list as [$obj, $value]) {
		if ($obj instanceof A) {
			assertType('int', $value);
		}
		if ($obj instanceof B) {
			assertType('string', $value);
		}
	}
}

/**
 * @param list<array{0, int}|array{1, string}|array{2, bool}> $list
 */
function threeVariants(array $list): void
{
	foreach ($list as [$tag, $value]) {
		if ($tag === 0) {
			assertType('int', $value);
		}
		if ($tag === 1) {
			assertType('string', $value);
		}
		if ($tag === 2) {
			assertType('bool', $value);
		}
	}
}

/**
 * @param list<array{tag: 'a', data: int}|array{tag: 'b', data: string}> $list
 */
function namedKeyDiscriminator(array $list): void
{
	foreach ($list as ['tag' => $tag, 'data' => $data]) {
		if ($tag === 'a') {
			assertType('int', $data);
		}
		if ($tag === 'b') {
			assertType('string', $data);
		}
	}
}

/**
 * Single-variant array — no tagged union, the per-variable narrowing applies
 * as before and the destructure-aware logic must be a no-op.
 *
 * @param list<array{int, string}> $list
 */
function singleVariant(array $list): void
{
	foreach ($list as [$x, $y]) {
		assertType('int', $x);
		assertType('string', $y);
	}
}

/**
 * Reassigning a destructured variable severs the destructure relationship
 * for that variable (PHPStan's existing invalidation handles this).
 *
 * @param list<array{null, int}|array{int, null}> $list
 */
function reassignmentInvalidates(array $list): void
{
	foreach ($list as [$x, $y]) {
		$x = null;
		// $y is still int|null — the holder for $x was invalidated by reassignment.
		assertType('int|null', $y);
	}
}

/**
 * Three-position tagged union — narrowing one position should pin the other
 * two for the matching variant.
 *
 * @param list<array{'a', int, true}|array{'b', string, false}> $list
 */
function threePositions(array $list): void
{
	foreach ($list as [$tag, $value, $flag]) {
		if ($tag === 'a') {
			assertType('int', $value);
			assertType('true', $flag);
		}
		if ($tag === 'b') {
			assertType('string', $value);
			assertType('false', $flag);
		}
	}
}
