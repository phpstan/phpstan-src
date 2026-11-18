<?php declare(strict_types = 1);

namespace Bug15767;

use function PHPStan\Testing\assertType;

/** @return finite-float */
function int_to_float(int $x): float {
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', (float) $x);
	return (float) $x;
}

/**
 * @param decimal-int-string $x
 * @return finite-float
 */
function decimal_int_string_to_float(string $x): float {
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', (float) $x);
	return (float) $x;
}

/**
 * @param decimal-int-string $d
 * @param non-decimal-int-string $nd
 * @param int<0, max> $nonNegative
 */
function alternatives(int $x, string $d, string $nd, int $nonNegative): void
{
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', floatval($x));
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', doubleval($d));
	assertType('float', (float) $nd);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $x + 0.0);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', 0.0 + $x);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $x * 1.0);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $x - 0.0);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $x / 1.0);
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $d + 0.0);
	assertType('float<0.0, 9.223372036854776E+18>', $nonNegative + 0.0);
	assertType('float<-1.8446744073709552E+19, 1.8446744073709552E+19>', $x * 2.0);
	assertType('float', $x * 1e300);
	assertType('(float<-4.611686018427388E+18, 4.611686018427388E+18>|int)', $x / 2);

	$y = $x;
	$y += 0.0;
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $y);

	$y = $x;
	settype($y, 'float');
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $y);

	$y = $d;
	settype($y, 'double');
	assertType('float<-9.223372036854776E+18, 9.223372036854776E+18>', $y);

	assertType('true', is_finite((float) $x));
	assertType('false', is_nan($x + 0.0));
}
