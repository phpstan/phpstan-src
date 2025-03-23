<?php

namespace Bug11942;

/**
 * @template A
 * @param A $a
 * @return A
 */
function identity(mixed $a): mixed
{
	return $a;
}

/**
 * @param callable(int): int $fn
 */
function simple(callable $fn): int
{
	return $fn(42);
}

simple(identity(...));
