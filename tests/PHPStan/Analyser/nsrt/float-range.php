<?php declare(strict_types = 1);

namespace FloatRange;

use function PHPStan\Testing\assertType;

/**
 * @param float<0, 10> $zeroToTen
 * @param float<-1.5, 2.5> $fractions
 * @param float<min, max> $notNan
 * @param float<min, 0> $upToZero
 * @param float<-0.0, 1> $negativeZeroBound
 * @param float<5, 5> $single
 * @param float<0, -0.0> $zero
 * @param positive-float $positive
 * @param non-negative-float $nonNegative
 * @param negative-float $negative
 * @param non-positive-float $nonPositive
 * @param finite-float $finite
 */
function phpDoc(
	$zeroToTen,
	$fractions,
	$notNan,
	$upToZero,
	$negativeZeroBound,
	$single,
	$zero,
	$positive,
	$nonNegative,
	$negative,
	$nonPositive,
	$finite
): void
{
	assertType('float<0.0, 10.0>', $zeroToTen);
	assertType('float<-1.5, 2.5>', $fractions);
	assertType('float<min, max>', $notNan);
	assertType('float<min, 0.0>', $upToZero);
	assertType('float<0.0, 1.0>', $negativeZeroBound);
	assertType('5.0', $single);
	assertType('0.0', $zero);
	assertType('float<5.0E-324, max>', $positive);
	assertType('float<0.0, max>', $nonNegative);
	assertType('float<min, -5.0E-324>', $negative);
	assertType('float<min, 0.0>', $nonPositive);
	assertType('float<-1.7976931348623157E+308, 1.7976931348623157E+308>', $finite);
}

/**
 * @param float<0, 1>|float<1, 2> $overlapping
 * @param float<0, 1>|float<1.0000000000000002, 2> $adjacent
 * @param float<0, 1>|float<3, 4> $disjoint
 * @param float<0, 1>|0.5 $containedConstant
 * @param float<0, 1>|1.0000000000000002 $adjacentConstant
 * @param float<0, 1>|float $withFloat
 * @param float<0, 10>&float<5, 20> $intersected
 * @param float<0, 1>&float<2, 3> $disjointIntersection
 * @param float<-0.0, 0>|float<5.0E-324, 1> $zeroAndPositive
 * @param positive-float|negative-float $nonZero
 */
function combinations(
	$overlapping,
	$adjacent,
	$disjoint,
	$containedConstant,
	$adjacentConstant,
	$withFloat,
	$intersected,
	$disjointIntersection,
	$zeroAndPositive,
	$nonZero
): void
{
	assertType('float<0.0, 2.0>', $overlapping);
	assertType('float<0.0, 2.0>', $adjacent);
	assertType('float<0.0, 1.0>|float<3.0, 4.0>', $disjoint);
	assertType('float<0.0, 1.0>', $containedConstant);
	assertType('float<0.0, 1.0000000000000002>', $adjacentConstant);
	assertType('float', $withFloat);
	assertType('float<5.0, 10.0>', $intersected);
	assertType('*NEVER*', $disjointIntersection);
	assertType('float<0.0, 1.0>', $zeroAndPositive);
	assertType('float<min, -5.0E-324>|float<5.0E-324, max>', $nonZero);
}

/**
 * @param float<0, 10> $zeroToTen
 * @param non-negative-float $nonNegative
 */
function narrowing(float $zeroToTen, float $nonNegative): void
{
	if ($zeroToTen === 5.5) {
		assertType('5.5', $zeroToTen);
	} else {
		assertType('float<0.0, 5.499999999999999>|float<5.500000000000001, 10.0>', $zeroToTen);
	}

	if ($zeroToTen !== 10.0) {
		assertType('float<0.0, 9.999999999999998>', $zeroToTen);
	}

	// -0.0 === 0.0, so removing the zero removes both zeros
	if ($nonNegative !== -0.0) {
		assertType('float<5.0E-324, max>', $nonNegative);
	}
}

/**
 * @param float<-5, -1> $negative
 * @param float<-2, 3> $aroundZero
 * @param float<1, 3> $positive
 * @param float<-2, 3> $withZero
 */
function conversions(float $negative, float $aroundZero, float $positive, float $withZero): void
{
	assertType('float<1.0, 5.0>', abs($negative));
	assertType('float<0.0, 3.0>', abs($aroundZero));
	assertType('float<1.0, 3.0>', abs($positive));
	assertType('true', (bool) $positive);
	assertType('bool', (bool) $withZero);
}

/**
 * @param float<-1.5, 2.5> $fractions
 * @param float<min, 0> $upToZero
 * @param float<0, 10> $zeroToTen
 * @param float<0, 1>|float<3, 4> $disjoint
 */
function negation(float $fractions, float $upToZero, float $zeroToTen, float $disjoint): void
{
	assertType('float<-2.5, 1.5>', -$fractions);
	assertType('float<0.0, max>', -$upToZero);
	assertType('float<-10.0, 0.0>', -$zeroToTen);
	assertType('float<-4.0, -3.0>|float<-1.0, 0.0>', -$disjoint);
}
