<?php declare(strict_types = 1);

namespace Bug15714ReturnType;

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function positive(float $t): float
{
	if ($t > 0.0) {
		return $t;
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function nanCheck(float $t): float
{
	if (is_nan($t)) {
		return $t;
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return float
 */
function widened(float $t): float
{
	if ($t > 0.0) {
		return $t;
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function returnsFloat(float $t, float $other): float
{
	if ($t > 0.0) {
		return $other;
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function absolute(float $t): float
{
	if ($t < 0.0) {
		return abs($t);
	}

	return abs($t);
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function merged(float $t): float
{
	$sign = $t >= 0.0 ? 'positive' : 'negative';

	return $t;
}
