<?php // lint >= 8.0

namespace FloatRangeFunctions;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param float<1.5, 2.5> $a
	 * @param float<-3.5, 4.5> $b
	 * @param float<-4, -1> $negative
	 * @param int<1, 5> $i
	 * @param positive-float $p
	 */
	public function rounding(float $a, float $b, float $negative, int $i, float $p, float $f): void
	{
		assertType('float<2.0, 3.0>', ceil($a));
		assertType('float<-3.0, 5.0>', ceil($b));
		assertType('float<-4.0, 4.0>', floor($b));
		assertType('float<1.0, 5.0>', ceil($i));
		assertType('2.0', ceil(1.5));
		assertType('-2.0', floor(-1.5));
		assertType('float<1.0, max>', ceil($p));
		assertType('float<0.0, max>', floor($p));
		assertType('float', ceil($f));

		// round() differs between PHP versions near .5, so it is bounded by floor() and ceil()
		assertType('float<1.0, 3.0>', round($a));
		assertType('float<-4.0, 5.0>', round($b));
		assertType('float<1.0, 2.0>', round(1.5));
		assertType('2.0', round(2.0));
		assertType('float<-4.0, -1.0>', round($negative, 0));
		assertType('float<min, max>', round($b, 1));
		assertType('float', round($f));
	}

	/**
	 * @param float<4, 9> $a
	 * @param float<-4, 9> $b
	 * @param float<-4, -1> $negative
	 * @param non-negative-float $nn
	 */
	public function sqrt(float $a, float $b, float $negative, float $nn, float $f): void
	{
		assertType('float<2.0, 3.0>', sqrt($a));
		assertType('NAN|float<0.0, 3.0>', sqrt($b));
		assertType('NAN', sqrt($negative));
		assertType('float<0.0, max>', sqrt($nn));
		assertType('2.0', sqrt(4.0));
		assertType('float', sqrt($f));
	}

	/**
	 * @param float<-7.5, 5> $x
	 * @param float<2, 3> $y
	 * @param float<-2, 3> $yWithZero
	 * @param finite-float $finite
	 * @param positive-float $p
	 */
	public function fmod(float $x, float $y, float $yWithZero, float $finite, float $p, float $f): void
	{
		assertType('float<-3.0, 3.0>', fmod($x, $y));
		assertType('NAN|float<-3.0, 3.0>', fmod($x, $yWithZero));
		assertType('NAN|float<-7.5, 5.0>', fmod($x, $finite));
		assertType('NAN|float<0.0, max>', fmod($p, $p));
		assertType('2.0', fmod(5.0, 3.0));
		assertType('float', fmod($f, $y));
	}

	/**
	 * @param list<int> $list
	 */
	public function producers(array $list): void
	{
		assertType('float<0.0, 1.0>', lcg_value());
		assertType('float<0.0, max>', microtime(true));

		// https://github.com/phpstan/phpstan/issues/6963
		assertType('float<0.0, max>|int<0, max>', count($list) / 4);
		assertType('float<0.0, max>', ceil(count($list) / 4));
	}

}
