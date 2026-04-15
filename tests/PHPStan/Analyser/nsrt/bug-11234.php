<?php // lint >= 8.0

namespace Bug11234;

use function PHPStan\Testing\assertType;

class Payload {}

/** @param array{0|1|2|3, int|Payload|string|null}&array{int, Payload} $x */
function testIntersectConstantUnionWithInt(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/** @param array{int, Payload}&array{0|1|2|3, int|Payload|string|null} $x */
function testIntersectConstantUnionWithIntReverse(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/** @param array{0|1|2|3, int|Payload|string|null}&array{0|1|2|3, Payload} $x */
function testIntersectBothConstantUnion(mixed $x): void
{
	assertType('array{0|1|2|3, Bug11234\Payload}', $x);
}

/** @param array{int, int|Payload|string|null}&array{int, Payload} $y */
function testIntersectPlainInt(mixed $y): void
{
	assertType('array{int, Bug11234\Payload}', $y);
}

/** @param array{0|1, string|int, Payload|null}&array{int, string, Payload} $z */
function testIntersectThreePositions(mixed $z): void
{
	assertType('array{0|1, string, Bug11234\Payload}', $z);
}

/** @param array{'a'|'b', int|Payload|string|null}&array{string, Payload} $w */
function testIntersectStringConstantUnion(mixed $w): void
{
	assertType("array{'a'|'b', Bug11234\Payload}", $w);
}

/** @param array{0|1, int|string}&array{int, int, extra?: bool} $v */
function testIntersectOptionalKey(mixed $v): void
{
	assertType('array{0|1, int}', $v);
}

/** @param array{true|false, int|string}&array{bool, string} $u */
function testIntersectBoolConstantUnion(mixed $u): void
{
	assertType('array{bool, string}', $u);
}

/** @param array{int<0, 3>, int|Payload|string|null}&array{int, Payload} $x */
function testIntersectIntRangeValue(mixed $x): void
{
	assertType('array{int<0, 3>, Bug11234\Payload}', $x);
}

/** @param array{non-empty-string, int|Payload|string|null}&array{string, Payload} $x */
function testIntersectNonEmptyStringValue(mixed $x): void
{
	assertType('array{non-empty-string, Bug11234\Payload}', $x);
}

/** @param array{0|1|2|3, non-empty-string|int|null}&array{int, string} $x */
function testIntersectNonEmptyStringInUnion(mixed $x): void
{
	assertType('array{0|1|2|3, non-empty-string}', $x);
}

/** @param array{0|1|2|3, string|null}&array{int, non-empty-string} $x */
function testIntersectWithNonEmptyStringOtherSide(mixed $x): void
{
	assertType('array{0|1|2|3, non-empty-string}', $x);
}
