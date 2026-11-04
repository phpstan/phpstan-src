<?php // lint >= 8.0

declare(strict_types = 1);

namespace FloatRangeNarrowing;

use function PHPStan\Testing\assertType;

function againstConstants(float $f): void
{
	if ($f < 5) {
		assertType('float<min, 4.999999999999999>', $f);
	} else {
		// NAN < 5 is false, so the false branch keeps NAN
		assertType('NAN|float<5.0, max>', $f);
	}

	if ($f >= 0) {
		// -0.0 >= 0 is true: the zero bound takes both zeros
		assertType('float<0.0, max>', $f);
	} else {
		assertType('NAN|float<min, -5.0E-324>', $f);
	}

	if ($f > 0.1) {
		assertType('float<0.10000000000000002, max>', $f);
	} else {
		assertType('NAN|float<min, 0.1>', $f);
	}

	if ($f <= -1.5) {
		assertType('float<min, -1.5>', $f);
	} else {
		assertType('NAN|float<-1.4999999999999998, max>', $f);
	}

	if (5 > $f) {
		assertType('float<min, 4.999999999999999>', $f);
	}

	if ($f > INF) {
		assertType('*NEVER*', $f);
	}

	if ($f < INF) {
		assertType('float<min, 1.7976931348623157E+308>', $f);
	}
}

/**
 * @param float<0, 10> $zeroToTen
 * @param float<1, 3> $limit
 */
function ranges(float $zeroToTen, float $limit, float $f): void
{
	if ($zeroToTen > 5) {
		assertType('float<5.000000000000001, 10.0>', $zeroToTen);
	} else {
		assertType('float<0.0, 5.0>', $zeroToTen);
	}

	if ($f < $limit) {
		assertType('float<min, 2.9999999999999996>', $f);
	} else {
		assertType('NAN|float<1.0, max>', $f);
	}

	if ($limit <= $f) {
		assertType('float<1.0, max>', $f);
	}

	if ($zeroToTen > $limit) {
		assertType('float<1.0000000000000002, 10.0>', $zeroToTen);
	}
}

/**
 * @param 0.0|'a' $zeroOrString
 */
function others(int|float $number, $zeroOrString): void
{
	if ($number < 0) {
		assertType('float<min, -5.0E-324>|int<min, -1>', $number);
	}

	// 0.0 >= -1 is true, so the zero stays
	if ($zeroOrString >= -1) {
		assertType("0.0|'a'", $zeroOrString);
	}

	if ($zeroOrString > -1) {
		assertType("0.0|'a'", $zeroOrString);
	}
}

/**
 * @param int<1, 5> $oneToFive
 * @param int<0, max> $nonNegative
 * @param int<min, -3> $upToMinusThree
 */
function casts(int $oneToFive, int $nonNegative, int $upToMinusThree, int $int): void
{
	assertType('float<1.0, 5.0>', (float) $oneToFive);
	assertType('float<0.0, 9.223372036854776E+18>', (float) $nonNegative);
	assertType('float<-9.223372036854776E+18, -3.0>', floatval($upToMinusThree));
	assertType('float', (float) $int);
}

/**
 * @param non-negative-float $nonNegative
 */
function checks(float $f, int|float $number, float $nonNegative): void
{
	if (is_nan($f)) {
		assertType('NAN', $f);
	} else {
		assertType('float<min, max>', $f);
	}

	if (is_finite($f)) {
		assertType('float<-1.7976931348623157E+308, 1.7976931348623157E+308>', $f);
	} else {
		assertType('-INF|INF|NAN', $f);
	}

	if (is_infinite($f)) {
		assertType('-INF|INF', $f);
	} else {
		assertType('NAN|float<-1.7976931348623157E+308, 1.7976931348623157E+308>', $f);
	}

	if (is_nan($number)) {
		assertType('NAN', $number);
	} else {
		assertType('float<min, max>|int', $number);
	}

	if (is_infinite($nonNegative)) {
		assertType('INF', $nonNegative);
	} else {
		assertType('float<0.0, 1.7976931348623157E+308>', $nonNegative);
	}

	assertType('true', is_nan(NAN));
	assertType('false', is_nan(1.5));
	assertType('false', is_finite(INF));
	assertType('true', is_infinite(-INF));
}
