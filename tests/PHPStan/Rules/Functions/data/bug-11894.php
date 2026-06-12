<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug11894;

/**
 * @template T of string|null
 * @param T $a
 */
function test(mixed $a): mixed
{
    if (!is_string($a)) {
        return $a;
    }

	return conditionalReturn($a);
}

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
function testNegated(mixed $a): mixed
{
    if (!is_string($a)) {
        return $a;
    }

	return conditionalReturnNegated($a);
}

/**
 * @template T
 * @param T $a
 * @return (T is not string ? T : string)
 */
function conditionalReturnNegated(mixed $a): mixed
{
    if (!is_string($a)) {
        return $a;
    }

    return trim($a);
}

/**
 * @template T of int|null
 * @param T $a
 */
function testNoRelation(mixed $a): mixed
{
    if (!is_int($a)) {
        return $a;
    }

	return conditionalReturn($a);
}

/**
 * @template T of string|int
 * @param T $a
 */
function testMaybeRelation(mixed $a): mixed
{
	return conditionalReturn($a);
}
