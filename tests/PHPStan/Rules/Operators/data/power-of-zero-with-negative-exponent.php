<?php declare(strict_types = 1); // lint >= 8.0

namespace PowerOfZeroWithNegativeExponent;

/**
 * @param 0|0.0 $zero
 * @param int<min, -1> $negative
 * @param int<-1, 1> $maybeNegative
 * @param int<0, 1> $maybeZero
 */
function doFoo(int|float $zero, int $negative, int $maybeNegative, int $maybeZero, float $float, string $numericZero, ?int $null): void
{
	var_dump(0 ** -1);
	var_dump(0.0 ** -0.5);
	var_dump($zero ** $negative);
	var_dump(pow(0, -2));
	var_dump(\pow(exponent: -2, num: 0));
	var_dump('0' ** -1);
	var_dump(null ** -1);
	$i = 0;
	$i **= -1;

	var_dump(0 ** 1);
	var_dump(0 ** 0);
	var_dump(0.0 ** -0.0);
	var_dump(2 ** -1);
	var_dump($zero ** $maybeNegative);
	var_dump($maybeZero ** $negative);
	var_dump($float ** -1);
	var_dump(fpow(0, -1));
}
