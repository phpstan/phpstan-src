<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11894Nsrt;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @param T $a
 * @return (T is string ? string : T)
 */
function conditionalReturn(mixed $a): mixed
{
	if (!is_string($a)) {
		return $a;
	}
	return trim($a);
}

/**
 * @template T of string|null
 * @param T $a
 */
function testNarrowedToString(mixed $a): void
{
	if (!is_string($a)) {
		return;
	}
	assertType('string', conditionalReturn($a));
}

/**
 * @template T of int|null
 * @param T $a
 */
function testNarrowedToNonMatchingType(mixed $a): void
{
	if (!is_int($a)) {
		return;
	}
	assertType('T of int (function Bug11894Nsrt\testNarrowedToNonMatchingType(), argument)', conditionalReturn($a));
}

/**
 * @template T of string|int
 * @param T $a
 */
function testNotFullyNarrowable(mixed $a): void
{
	assertType('string|T of int (function Bug11894Nsrt\testNotFullyNarrowable(), argument)', conditionalReturn($a));
}
