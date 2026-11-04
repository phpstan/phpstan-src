<?php // lint >= 8.0

namespace FloatRangeArithmetic;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param float<1, 2> $a
	 * @param float<-3, 4> $b
	 * @param positive-float $p
	 * @param non-negative-float $nn
	 * @param finite-float $fin
	 * @param int<1, 5> $i
	 */
	public function doFoo(float $a, float $b, float $p, float $nn, float $fin, int $i, float $f, int $int): void
	{
		assertType('float<2.0, 4.0>', $a + $a);
		assertType('float<2.0, 7.0>', $a + $i);
		assertType('float<2.0, 7.0>', $i + $a);
		assertType('float<-3.0, 5.0>', $a - $b);
		assertType('float<-6.0, 8.0>', $a * $b);
		assertType('float<-3.0, 4.0>', $b * 1);

		// a non-int bounded operand stays unbounded on that side
		assertType('float<min, max>', $a + $int);
		assertType('float', $a + $f);

		// overflow reaches the infinities
		assertType('float<min, max>', $fin + $fin);
		assertType('float<1.0E-323, max>', $p + $p);

		// INF - INF and 0 * INF are NAN
		assertType('float', $p - $p);
		assertType('NAN|float<0.0, max>', $nn * $p);
		assertType('float', $b * $p);

		// division by a zero throws on PHP 8, so zero divisors are left out
		assertType('float<0.0, max>', $a / $p);
		assertType('float<0.0, max>', $a / $nn);
		assertType('float<min, -0.3333333333333333>|float<0.25, max>', $a / $b);
		assertType('float<0.5, 1.0>', $a / 2);
		assertType('*ERROR*', $a / 0);

		// union members are combined pairwise
		$u = $i > 2 ? $a : $i;
		assertType('float<2.0, 3.0>|int<2, 3>', $u + 1);
		assertType('float<0.0, 1.0>|int<0, 1>', $u - 1);

		$x = $a;
		$x++;
		assertType('float<2.0, 3.0>', $x);
		$x += 0.5;
		assertType('float<2.5, 3.5>', $x);
		$x--;
		assertType('float<1.5, 2.5>', $x);
	}

	/**
	 * @param float<1, 2> $a
	 * @param float<-3.5, 4.5> $b
	 * @param positive-float $p
	 * @param non-negative-float $nn
	 * @param float<0, 1.0E+20> $huge
	 */
	public function casts(float $a, float $b, float $p, float $nn, float $huge): void
	{
		assertType('non-falsy-string&numeric-string&uppercase-string', (string) $a);
		assertType('numeric-string&uppercase-string', (string) $b);
		assertType('non-falsy-string&uppercase-string', (string) $p);
		assertType('non-empty-string&uppercase-string', (string) $nn);

		assertType('int<1, 2>', (int) $a);
		assertType('int<-3, 4>', (int) $b);
		assertType('int', (int) $p);
		assertType('int', (int) $huge);
	}

}
