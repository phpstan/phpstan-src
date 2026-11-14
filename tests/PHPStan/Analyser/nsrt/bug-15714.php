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

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function absolute(float $t): float
{
	assertType('float', abs($t));

	if ($t < 0.0) {
		assertType('T of float<min, -5.0E-324> (function Bug15714\absolute(), argument)', $t);
		assertType('float<5.0E-324, max>', abs($t));
	}

	return $t;
}

/**
 * @template T of int
 * @param T $t
 * @return T
 */
function absoluteInt(int $t): int
{
	assertType('int<0, max>', abs($t));

	if ($t < 0) {
		assertType('int<0, max>', abs($t));
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function constant(float $t): float
{
	if ($t >= 1.5 && $t <= 1.5) {
		assertType('T of 1.5 (function Bug15714\constant(), argument)', $t);
		return $t;
	}

	return $t;
}

/**
 * @template T of float
 * @param T $t
 * @return T
 */
function merged(float $t): float
{
	$sign = $t >= 0.0 ? 'positive' : 'negative';
	assertType('T of float<0.0, max> (function Bug15714\merged(), argument)|(T of NAN|float<min, -5.0E-324> (function Bug15714\merged(), argument))', $t);

	return $t;
}

/**
 * @template T of float|int
 * @param T $t
 * @return T
 */
function unionBound($t)
{
	if (is_float($t) && $t > 0.0) {
		assertType('T of float<5.0E-324, max> (function Bug15714\unionBound(), argument)', $t);
		return $t;
	}

	return $t;
}

/**
 * @template T of float<0, 1>
 * @param T $t
 * @return T
 */
function rangeBound(float $t): float
{
	assertType('T of float<0.0, 1.0> (function Bug15714\rangeBound(), argument)', $t);
	assertType('float<0.0, 1.0>', abs($t));

	return $t;
}

/**
 * @template T of 1.5
 * @param T $t
 * @return T
 */
function constantBound(float $t): float
{
	assertType('T of 1.5 (function Bug15714\constantBound(), argument)', $t);

	return $t;
}

function explicitBounds(): void
{
	assertType('0.5', rangeBound(0.5));
	assertType('1.5', constantBound(1.5));
}
