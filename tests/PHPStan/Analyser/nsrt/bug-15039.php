<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15039;

use function PHPStan\Testing\assertType;

function repro(int $v): void
{
	assert(!(is_int($v) && $v < 0) && !(is_int($v) && $v >= 1));

	assertType('0', $v);
}

function chained(int $v): void
{
	assert(is_int($v) && !(is_int($v) && $v < 0) && !(is_int($v) && $v >= 1));

	assertType('0', $v);
}

function doubleNegation(int $v): void
{
	if (!(!(!(is_int($v) && $v < 0) && !(is_int($v) && $v >= 1)))) {
		assertType('0', $v);
	}
}

function threeAlternatives(int $v): void
{
	assert(!(is_int($v) && $v < 0) && !(is_int($v) && $v > 5) && !(is_int($v) && $v === 3));

	assertType('int<0, 2>|int<4, 5>', $v);
}

// the `&&` chain below is deep enough for BooleanAndHandler to take its
// flattened path, which used to merge the arms without unionWith()'s semantics
function flattenedAnd(int $v, bool $a, bool $b, bool $c, bool $d): void
{
	assert($a && $b && $c && $d && !(is_int($v) && $v < 0) && !(is_int($v) && $v >= 1));

	assertType('0', $v);
}

function flattenedLogicalAnd(int $v, bool $a, bool $b, bool $c, bool $d): void
{
	assert($a and $b and $c and $d and !(is_int($v) && $v < 0) and !(is_int($v) && $v >= 1));

	assertType('0', $v);
}

function flattenedAndCollidingSureTypes(int|string|float $v, bool $a, bool $b, bool $c, bool $d): void
{
	assert($a && $b && $c && $d && (is_int($v) || is_string($v)) && (is_int($v) || is_float($v)));

	assertType('int', $v);
}

function shallowAndCollidingSureTypes(int|string|float $v, bool $a): void
{
	assert($a && (is_int($v) || is_string($v)) && (is_int($v) || is_float($v)));

	assertType('int', $v);
}

// same for the falsey narrowing of a deep `||` chain
function flattenedOr(int $v, bool $a, bool $b, bool $c, bool $d): void
{
	assert(!($a || $b || $c || $d || (is_int($v) && $v < 0) || (is_int($v) && $v >= 1)));

	assertType('0', $v);
}

function shallowOr(int $v): void
{
	assert(!((is_int($v) && $v < 0) || (is_int($v) && $v >= 1)));

	assertType('0', $v);
}

// one side's alternative form carries the extra term of an inner `||`
function alternativeWithExtraTerm(int $v): void
{
	assert((!(is_int($v) && $v < 0) || $v === -5) && !(is_int($v) && $v >= 1));

	assertType('-5|0', $v);
}

// an alternative form and a plain entry on the same expression still conjoin
function alternativeAndSureType(int $v): void
{
	assert(!(is_int($v) && $v < 0) && $v < 3);

	assertType('int<0, 2>', $v);
}

function subtractedObject(object $o): void
{
	if (!($o instanceof \ArrayObject)) {
		assertType('object~ArrayObject', $o);

		if (!($o instanceof \Traversable)) {
			assertType('object~Traversable', $o);
		}
	}
}
