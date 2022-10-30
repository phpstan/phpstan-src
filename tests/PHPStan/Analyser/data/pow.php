<?php

namespace PowFunction;

use function PHPStan\Testing\assertType;

function ($a, $b): void {
	assertType('(float|int)', pow($a, $b));
	assertType('(float|int)', $a ** $b);
};

/**
 * @param numeric-string $numericS
 */
function doFoo(int $a, int $b, string $s, bool $c, $numericS, float $f): void {
	$constString = "hallo";
	$constNumericString = "123";
	$true = true;
	$null = null;
	$one = 1;

	assertType('(float|int)', pow($a, $b));
	assertType('(float|int)', $a ** $b);

	assertType('(float|int)', pow($a, $numericS));
	assertType('(float|int)', $a ** $numericS);
	assertType('(float|int)', $numericS ** $numericS);
	assertType('(float|int)', pow($a, $constNumericString));
	assertType('(float|int)', $a ** $constNumericString);

	assertType('*ERROR*', pow($a, $s));
	assertType('*ERROR*', $a ** $s);
	assertType('*ERROR*', pow($a, $constString));
	assertType('*ERROR*', $a ** $constString);

	assertType('(float|int)', pow($a, $c));
	assertType('(float|int)', $a ** $c);
	assertType('int', pow($a, $true));
	assertType('int', $a ** $true);

	assertType('0', pow($null, $constNumericString));
	assertType('1', pow($null, $null));
	assertType('0|1', pow($null, $a));
	assertType('1', $constNumericString ** $null);
	assertType('1', $a ** $null);
	assertType('float', $f ** $null); // could be 1.0

	assertType('625', pow('5', '4'));
	assertType('625', '5' ** '4');

	assertType('int', pow($a, $one));
	assertType('int', $a ** '1');
	assertType('float', pow($f, $one));
	assertType('float', $f ** '1');

	assertType('1', pow($a, 0));
	assertType('1', $a ** '0');
	assertType('1.0', pow($f, 0));
	assertType('1.0', $f ** '0');
	assertType('float', $f ** false); // could be 1.0

	assertType('NAN', pow(-1,5.5));
};

function (\GMP $a, \GMP $b): void {
	assertType('GMP', pow($a, $b));
	assertType('GMP', $a ** $b);
};

function (\stdClass $a, \GMP $b): void {
	assertType('GMP|stdClass', pow($a, $b));
	assertType('GMP|stdClass', $a ** $b);

	assertType('GMP|stdClass', pow($b, $a));
	assertType('GMP|stdClass', $b ** $a);
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
 * @param int<-6, -4>|int<-2, -1> $unionRange1
 * @param int<4, 6>|int<1, 2> $unionRange2
 */
function foo($positiveInt, $range2, $unionRange1, $unionRange2): void {
	$range = rand(2, 3);

	assertType('int<2, max>', pow($range, $positiveInt));
	assertType('int<2, max>', $range ** $positiveInt);

	assertType('int<min, 27>', pow($range, $range2));
	assertType('int<min, 27>', $range ** $range2);

	assertType('(float|int)', pow($range, PHP_INT_MAX));
	assertType('(float|int)', $range ** PHP_INT_MAX);

	assertType('(float|int)', pow($range2, $positiveInt));
	assertType('(float|int)', $range2 ** $positiveInt);

	assertType('(float|int)', pow($positiveInt, $range2));
	assertType('(float|int)', $positiveInt ** $range2);

	assertType('int<-6, 16>|int<1296, 4096>', pow($unionRange1, $unionRange2));
	assertType('int<-6, 16>|int<1296, 4096>', $unionRange1 ** $unionRange2);

	assertType('int<2, 4>|int<16, 64>', pow(2, $unionRange2));
	assertType('int<2, 4>|int<16, 64>', 2 ** $unionRange2);
}
