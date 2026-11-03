<?php

namespace PowerIntegerIntervals;

use function PHPStan\Testing\assertType;

/**
 * @param int<0, 10> $zeroToTen
 * @param int<0, 3> $zeroToThree
 * @param int<-5, 5> $minusFiveToFive
 * @param int<-3, -2> $minusThreeToMinusTwo
 * @param int<min, -1> $negative
 * @param int<-9223372036854775808, 5> $fromIntMin
 * @param numeric-string $numericString
 */
function doFoo(int $int, float $float, $zeroToTen, $zeroToThree, $minusFiveToFive, $minusThreeToMinusTwo, $negative, $fromIntMin, string $numericString): void
{
	// null and false are 0 as a base: 0 ** 0 is 1, 0 ** 1 is 0
	assertType('int<0, 1>', null ** $zeroToTen);
	assertType('int<0, 1>', false ** $zeroToTen);
	assertType('int<0, 1>', 0 ** $zeroToTen);
	assertType('int<0, 10000000000>', $zeroToTen ** $zeroToTen);
	assertType('int<1, 1024>', 2 ** $zeroToTen);

	// a negative base alternates its sign
	assertType('-1|1', (-1) ** $zeroToTen);
	assertType('int<0, 25>', $minusFiveToFive ** 2);
	assertType('int<-125, 125>', $minusFiveToFive ** 3);
	assertType('int<4, 9>', $minusThreeToMinusTwo ** 2);
	assertType('int<-27, -8>', $minusThreeToMinusTwo ** 3);
	assertType('1|int<-27, -8>|int<-3, -2>|int<4, 9>', $minusThreeToMinusTwo ** $zeroToThree);
	assertType('int<min, -1>', $negative ** 9223372036854775807);
	assertType('(float|int)', $fromIntMin ** 2);

	// a float operand makes the result a float
	assertType('float', null ** $float);
	assertType('float', $int ** $float);

	// anything to the power of 0 is 1, or 1.0 for a float base
	assertType('1', $int ** 0);
	assertType('1.0', $float ** 0);
	assertType('(1|1.0)', $numericString ** 0);

	// a non-numeric string operand is a TypeError
	assertType('*ERROR*', '' ** $int);
	assertType('*ERROR*', $int ** 'abc');
}
