<?php

namespace PowFunction;

use function PHPStan\Testing\assertType;

function ($a, $b): void {
	assertType('(float|int)', pow($a, $b));
	assertType('(float|int)', $a ** $b);
};

function (int $a, int $b): void {
	assertType('(float|int)', pow($a, $b));
	assertType('(float|int)', $a ** $b);
};

function (\GMP $a, \GMP $b): void {
	assertType('GMP', pow($a, $b));
	assertType('GMP', $a ** $b);
};

function (\stdClass $a, \GMP $b): void {
	assertType('GMP|stdClass', pow($a, $b));
	assertType('GMP|stdClass', $a ** $b);
};

function (): void {
	$range = rand(1, 3);
	assertType('int<1, 3>', $range);

	assertType('int<1, 9>', pow($range, 2));
	assertType('int<1, 9>', $range ** 2);

	assertType('int<2, 8>', pow(2, $range));
	assertType('int<2, 8>', 2 ** $range);
};

function (): void {
	$range = rand(2, 3);
	$x = 2;
	if (rand(0, 1)) {
		$x = 3;
	} else if (rand(0, 10)) {
		$x = 4;
	}

	assertType('int<4, 27>|int<16, 81>', pow($range, $x));
	assertType('int<4, 27>|int<16, 81>', $range ** $x);

	assertType('int<4, 27>|int<16, 64>', pow($x, $range));
	assertType('int<4, 27>|int<16, 64>', $x ** $range);

	assertType('int<4, 27>', pow($range, $range));
	assertType('int<4, 27>', $range ** $range);
};

/**
 * @param positive-int $positiveInt
 * @param int<min, 3> $range2
 */
function foo($positiveInt, $range2): void {
	$range = rand(2, 3);

	assertType('int<2, max>', pow($range, $positiveInt));
	assertType('int<2, max>', $range ** $positiveInt);

	assertType('int<min, 27>', pow($range, $range2));
	assertType('int<min, 27>', $range ** $range2);

	assertType('(float|int)', pow($range, PHP_INT_MAX));
	assertType('(float|int)', $range ** PHP_INT_MAX);
}
