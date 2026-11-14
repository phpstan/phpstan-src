<?php declare(strict_types = 1);

namespace Bug15714;

use function PHPStan\Testing\assertType;

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function positive(float $t): float
{
	if ($t > 0.0) {
		assertType('T of float<5.0E-324, max> (function Bug15714\positive(), argument)', $t);
		return $t;
	}

	assertType('T of NAN|float<min, 0.0> (function Bug15714\positive(), argument)', $t);

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
		assertType('T of NAN (function Bug15714\nanCheck(), argument)', $t);
		return $t;
	}

	assertType('T of float<min, max> (function Bug15714\nanCheck(), argument)', $t);

	return $t;
}

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function positiveInt(int $t): int
{
	if ($t > 0) {
		assertType('T of int<1, max> (function Bug15714\positiveInt(), argument)', $t);
		return $t;
	}

	assertType('T of int<min, 0> (function Bug15714\positiveInt(), argument)', $t);

	return $t;
}
