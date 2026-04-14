<?php

namespace Bug11234;

use function PHPStan\Testing\assertType;

class Payload {}

/**
 * When the value at index 0 is a union of constants (0|1|2|3), int leaks
 * into the resolved type of the adjacent value during intersection.
 * The generalization of 0|1|2|3 to int causes getIterableValueType()
 * to include int in the value union, leaking it to position 1.
 *
 * @param array{0|1|2|3, int|Payload|string|null}&array{int, Payload} $x
 */
function testIntersectConstantUnionWithInt(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/**
 * Reverse order.
 *
 * @param array{int, Payload}&array{0|1|2|3, int|Payload|string|null} $x
 */
function testIntersectConstantUnionWithIntReverse(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/**
 * Both sides have constant unions.
 *
 * @param array{0|1|2|3, int|Payload|string|null}&array{0|1|2|3, Payload} $x
 */
function testIntersectBothConstantUnion(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/**
 * Works fine when the first value is just int.
 *
 * @param array{int, int|Payload|string|null}&array{int, Payload} $y
 */
function testIntersectPlainInt(mixed $y): void
{
	assertType('array{int, Bug11234\Payload}', $y);
}

/**
 * Three-value array shape: leaks should not happen across multiple positions.
 *
 * @param array{0|1, string|int, Payload|null}&array{int, string, Payload} $z
 */
function testIntersectThreePositions(mixed $z): void
{
	assertType('array{0|1, string, Bug11234\Payload}', $z);
}

/**
 * String constant union at first position.
 *
 * @param array{'a'|'b', int|Payload|string|null}&array{string, Payload} $w
 */
function testIntersectStringConstantUnion(mixed $w): void
{
	assertType("array{'a'|'b', Bug11234\Payload}", $w);
}

/**
 * Different key count — extra keys from one side are dropped.
 *
 * @param array{0|1, int|string}&array{int, int, extra?: bool} $v
 */
function testIntersectOptionalKey(mixed $v): void
{
	assertType('array{0|1, int}', $v);
}

/**
 * Boolean constant union.
 *
 * @param array{true|false, int|string}&array{bool, string} $u
 */
function testIntersectBoolConstantUnion(mixed $u): void
{
	assertType('array{bool, string}', $u);
}
