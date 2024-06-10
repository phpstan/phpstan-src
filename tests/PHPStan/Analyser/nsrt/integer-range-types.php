<?php

namespace PHPStan\Generics\GenericClassStringType;

use function PHPStan\Testing\assertType;

function (int $i) {
	if ($i < 3) {
		assertType('int<min, 2>', $i);

		$i++;
		assertType('int<min, 3>', $i);
	} else {
		assertType('int<3, max>', $i);
	}

	if ($i < 3) {
		assertType('int<min, 2>', $i);

		$i--;
		assertType('int<min, 1>', $i);
	}

	assertType('int<min, 1>|int<3, max>', $i);

	if ($i < 3 && $i > 5) {
		assertType('*NEVER*', $i);
	} else {
		assertType('int<min, 1>|int<3, max>', $i);
	}

	if ($i > 3 && $i < 5) {
		assertType('4', $i);
	} else {
		assertType('3|int<min, 1>|int<5, max>', $i);
	}

	if ($i >= 3 && $i <= 5) {
		assertType('int<3, 5>', $i);

		if ($i === 2) {
			assertType('*NEVER*', $i);
		} else {
			assertType('int<3, 5>', $i);
		}

		if ($i !== 3) {
			assertType('int<4, 5>', $i);
		} else {
			assertType('3', $i);
		}
	}
};


function () {
	for ($i = 0; $i < 5; $i++) {
		assertType('int<0, 4>', $i);
	}

	$i = 0;
	while ($i < 5) {
		assertType('int<0, 4>', $i);
		$i++;
	}

	$i = 0;
	while ($i++ < 5) {
		assertType('int<1, 5>', $i);
	}

	$i = 0;
	while (++$i < 5) {
		assertType('int<1, 4>', $i);
	}

	$i = 5;
	while ($i-- > 0) {
		assertType('int<0, 4>', $i);
	}

	$i = 5;
	while (--$i > 0) {
		assertType('int<1, 4>', $i);
	}
};


function (int $j) {
	$i = 1;

	assertType('true', $i > 0);
	assertType('true', $i >= 1);
	assertType('true', $i <= 1);
	assertType('true', $i < 2);

	assertType('false', $i < 1);
	assertType('false', $i <= 0);
	assertType('false', $i >= 2);
	assertType('false', $i > 1);

	assertType('true', 0 < $i);
	assertType('true', 1 <= $i);
	assertType('true', 1 >= $i);
	assertType('true', 2 > $i);

	assertType('bool', $j > 0);
	assertType('bool', $j >= 0);
	assertType('bool', $j <= 0);
	assertType('bool', $j < 0);

	if ($j < 5) {
		assertType('bool', $j > 0);
		assertType('false', $j > 4);
		assertType('bool', 0 < $j);
		assertType('false', 4 < $j);

		assertType('bool', $j >= 0);
		assertType('false', $j >= 5);
		assertType('bool', 0 <= $j);
		assertType('false', 5 <= $j);

		assertType('true', $j <= 4);
		assertType('bool', $j <= 3);
		assertType('true', 4 >= $j);
		assertType('bool', 3 >= $j);

		assertType('true', $j < 5);
		assertType('bool', $j < 4);
		assertType('true', 5 > $j);
		assertType('bool', 4 > $j);
	}
};

function (int $a, int $b, int $c): void {

	if ($a <= 11) {
		return;
	}

	assertType('int<12, max>', $a);

	if ($b <= 12) {
		return;
	}

	assertType('int<13, max>', $b);

	if ($c <= 13) {
		return;
	}

	assertType('int<14, max>', $c);

	assertType('int<156, max>', $a * $b);
	assertType('int<182, max>', $b * $c);
	assertType('int<2184, max>', $a * $b * $c);
};

class X {
	/**
	 * @var int<0, 100>
	 */
	public $percentage;
	/**
	 * @var int<min, 100>
	 */
	public $min;
	/**
	 * @var int<0, max>
	 */
	public $max;

	/**
	 * @var int<0, something>
	 */
	public $error1;
	/**
	 * @var int<something, 100>
	 */
	public $error2;

	/**
	 * @var int<min, max>
	 */
	public $int;

	public function supportsPhpdocIntegerRange() {
		assertType('int<0, 100>', $this->percentage);
		assertType('int<min, 100>', $this->min);
		assertType('int<0, max>', $this->max);

		assertType('*ERROR*', $this->error1);
		assertType('*ERROR*', $this->error2);
		assertType('int', $this->int);
	}

	/**
	 * @param int $i
	 * @param 1|2|3 $j
	 * @param 1|-20|3 $z
	 * @param positive-int $pi
	 * @param int<1, 10> $r1
	 * @param int<5, 10> $r2
	 * @param int<-9, 100> $r3
	 * @param int<min, 5> $rMin
	 * @param int<5, max> $rMax
	 *
	 * @param 20|40|60 $x
	 * @param 2|4 $y
	 */
	public function math($i, $j, $z, $pi, $r1, $r2, $r3, $rMin, $rMax, $x, $y) {
		assertType('int', $r1 + $i);
		assertType('int', $r1 - $i);
		assertType('int', $r1 * $i);
		assertType('(float|int)', $r1 / $i);

		assertType('int<2, 13>', $r1 + $j);
		assertType('int<-2, 9>', $r1 - $j);
		assertType('int<1, 30>', $r1 * $j);
		assertType('float|int<1, 10>', $r1 / $j);
		assertType('int<min, 15>', $rMin * $j);
		assertType('int<5, max>', $rMax * $j);

		assertType('int<2, 13>', $j + $r1);
		assertType('int<-9, 2>', $j - $r1);
		assertType('int<1, 30>', $j * $r1);
		assertType('float|int<1, 3>', $j / $r1);
		assertType('int<min, 15>', $j * $rMin);
		assertType('int<5, max>', $j * $rMax);

		assertType('int<-19, -10>|int<2, 13>', $r1 + $z);
		assertType('int<-2, 9>|int<21, 30>', $r1 - $z);
		assertType('int<-200, -20>|int<1, 30>', $r1 * $z);
		assertType('float|int<1, 10>', $r1 / $z);
		assertType('int', $rMin * $z);
		assertType('int<min, -100>|int<5, max>', $rMax * $z);

		assertType('int<2, max>', $pi + 1);
		assertType('int<-1, max>', $pi - 2);
		assertType('int<2, max>', $pi * 2);
		assertType('float|int<1, max>', $pi / 2);
		assertType('int<2, max>', 1 + $pi);
		assertType('int<min, 2>', 2 - $pi);
		assertType('int<2, max>', 2 * $pi);
		assertType('float|int<1, 2>', 2 / $pi);

		assertType('int<5, 14>', $r1 + 4);
		assertType('int<-3, 6>', $r1 - 4);
		assertType('int<4, 40>', $r1 * 4);
		assertType('float|int<1, 2>', $r1 / 4);
		assertType('int<9, max>', $rMax + 4);
		assertType('int<1, max>', $rMax - 4);
		assertType('int<20, max>', $rMax * 4);
		assertType('float|int<2, max>', $rMax / 4);

		assertType('int<6, 20>', $r1 + $r2);
		assertType('int<-9, 5>', $r1 - $r2);
		assertType('int<5, 100>', $r1 * $r2);
		assertType('float|int<1, 2>', $r1 / $r2);

		assertType('int<-99, 19>', $r1 - $r3);

		assertType('int<min, 15>', $r1 + $rMin);
		assertType('int<-4, max>', $r1 - $rMin);
		assertType('int<min, 50>', $r1 * $rMin);
		assertType('float|int<-10, -1>|int<1, 10>', $r1 / $rMin);
		assertType('int<min, 15>', $rMin + $r1);
		assertType('int<min, 4>', $rMin - $r1);
		assertType('int<min, 50>', $rMin * $r1);
		assertType('float|int<min, 5>', $rMin / $r1);

		assertType('int<6, max>', $r1 + $rMax);
		assertType('int', $r1 - $rMax);
		assertType('int<5, max>', $r1 * $rMax);
		assertType('float|int<1, 2>', $r1 / $rMax);
		assertType('int<6, max>', $rMax + $r1);
		assertType('int<-5, max>', $rMax - $r1);
		assertType('int<5, max>', $rMax * $r1);
		assertType('float|int<1, max>', $rMax / $r1);

		assertType('5|10|15|20|30', $x / $y);

		assertType('float|int<1, max>', $rMax / $rMax);
		assertType('(float|int)', $rMin / $rMin);
	}

	/**
	 * @param int<0, max> $a
	 * @param int<0, max> $b
	 */
	function divisionLoosesInformation(int $a, int $b): void {
		assertType('float|int<0, max>',$a/$b);
	}

	/**
	 * @param int<min, 5> $rMin
	 * @param int<5, max> $rMax
	 *
	 * @see https://www.wolframalpha.com/input/?i=%28interval%5B2%2C%E2%88%9E%5D+%2F+-1%29
	 * @see https://3v4l.org/ur9Wf
	 */
	public function maximaInversion($rMin, $rMax) {
		assertType('int<-5, max>', -1 * $rMin);
		assertType('int<min, -10>', -2 * $rMax);

		assertType('int<-5, max>', $rMin * -1);
		assertType('int<min, -10>', $rMax * -2);

		assertType('-1|1|float', -1 / $rMin);
		assertType('float', -2 / $rMax);

		assertType('float|int<-5, max>', $rMin / -1);
		assertType('float|int<min, -2>', $rMax / -2);
	}

	/**
	 * @param int<1, 10> $r1
	 * @param int<-5, 10> $r2
	 * @param int<min, 5> $rMin
	 * @param int<5, max> $rMax
	 * @param int<0, 50> $rZero
	 */
	public function unaryMinus($r1, $r2, $rMin, $rMax, $rZero) {

		assertType('int<-10, -1>', -$r1);
		assertType('int<-10, 5>', -$r2);
		assertType('int<-5, max>', -$rMin);
		assertType('int<min, -5>', -$rMax);
		assertType('int<-50, 0>', -$rZero);
	}

	/**
	 * @param int<-1, 2> $p
	 * @param int<-1, 2> $u
	 */
	public function sayHello($p, $u): void
	{
		assertType('int<-2, 4>', $p + $u);
		assertType('int<-3, 3>', $p - $u);
		assertType('int<-2, 4>', $p * $u);
		assertType('float|int<-2, 2>', $p / $u);
	}

	/**
	 * @param int<-5, 5> $a
	 * @param int<5, max> $b
	 * @param int<min, -5> $c
	 * @param 1|int<5, 10>|25|int<30, 40> $d
	 * @param 1|3.0|"5" $e
	 * @param 1|"ciao" $f
	 */
	public function shiftLeft($a, $b, $c, $d, $e, $f): void
	{
		assertType('int<-5, 5>', $a << 0);
		assertType('int<5, max>', $b << 0);
		assertType('int<min, -5>', $c << 0);
		assertType('1|25|int<5, 10>|int<30, 40>', $d << 0);
		assertType('1|3|5', $e << 0);
		assertType('*ERROR*', $f << 0);

		assertType('int<-10, 10>', $a << 1);
		assertType('int<10, max>', $b << 1);
		assertType('int<min, -10>', $c << 1);
		assertType('2|50|int<10, 20>|int<60, 80>', $d << 1);
		assertType('2|6|10', $e << 1);
		assertType('*ERROR*', $f << 1);

		assertType('*ERROR*', $a << -1);

		assertType('int', $a << $b);

		assertType('0', null << 1);
		assertType('0', false << 1);
		assertType('2', true << 1);
		assertType('10', "10" << 0);
		assertType('*ERROR*', "ciao" << 0);
		assertType('30', 15.9 << 1);
		assertType('*ERROR*', array(5) << 1);

		assertType('8', 4.1 << 1.9);

		/** @var float */
		$float = 4.1;
		assertType('int', $float << 1.9);
	}

	/**
	 * @param int<-5, 5> $a
	 * @param int<5, max> $b
	 * @param int<min, -5> $c
	 * @param 1|int<5, 10>|25|int<30, 40> $d
	 * @param 1|3.0|"5" $e
	 * @param 1|"ciao" $f
	 */
	public function shiftRight($a, $b, $c, $d, $e, $f): void
	{
		assertType('int<-5, 5>', $a >> 0);
		assertType('int<5, max>', $b >> 0);
		assertType('int<min, -5>', $c >> 0);
		assertType('1|25|int<5, 10>|int<30, 40>', $d >> 0);
		assertType('1|3|5', $e >> 0);
		assertType('*ERROR*', $f >> 0);

		assertType('int<-3, 2>', $a >> 1);
		assertType('int<2, max>', $b >> 1);
		assertType('int<min, -3>', $c >> 1);
		assertType('0|12|int<2, 5>|int<15, 20>', $d >> 1);
		assertType('0|1|2', $e >> 1);
		assertType('*ERROR*', $f >> 1);

		assertType('*ERROR*', $a >> -1);

		assertType('int', $a >> $b);

		assertType('0', null >> 1);
		assertType('0', false >> 1);
		assertType('0', true >> 1);
		assertType('10', "10" >> 0);
		assertType('*ERROR*', "ciao" >> 0);
		assertType('7', 15.9 >> 1);
		assertType('*ERROR*', array(5) >> 1);

		assertType('2', 4.1 >> 1.9);

		/** @var float */
		$float = 4.1;
		assertType('int', $float >> 1.9);
	}

	/**
	 * @param int<0, max> $positive
	 * @param int<min, 0> $negative
	 */
	public function zeroIssues($positive, $negative)
	{
		assertType('0', 0 * $positive);
		assertType('int<0, max>', $positive * $positive);
		assertType('0', 0 * $negative);
		assertType('int<0, max>', $negative * $negative);
		assertType('int<min, 0>', $negative * $positive);
	}

}
