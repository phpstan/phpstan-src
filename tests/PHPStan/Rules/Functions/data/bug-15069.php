<?php declare(strict_types = 1);

namespace Bug15069;

/**
 * @param mixed $maybeint
 */
function absint($maybeint): int
{
	return abs((int) $maybeint);
}

function absNonNegative(int $int): int
{
	if ($int < 0) {
		return 0;
	}

	return abs($int);
}

function negate(int $int): int
{
	return -$int;
}

/**
 * @param int<-5, 5> $int
 */
function negateBoundedRange(int $int): int
{
	return -$int;
}
