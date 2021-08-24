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
		assertType('int<min, 4>', $i); // should improved to be int<0, 4>
	}

	$i = 0;
	while ($i < 5) {
		assertType('int<min, 4>', $i); // should improved to be int<0, 4>
		$i++;
	}

	$i = 0;
	while ($i++ < 5) {
		assertType('int<min, 5>', $i); // should improved to be int<1, 5>
	}

	$i = 0;
	while (++$i < 5) {
		assertType('int<min, 4>', $i); // should improved to be int<1, 4>
	}

	$i = 5;
	while ($i-- > 0) {
		assertType('int<0, max>', $i); // should improved to be int<0, 4>
	}

	$i = 5;
	while (--$i > 0) {
		assertType('int<1, max>', $i); // should improved to be int<1, 4>
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
	 * @param positive-int $pi
	 * @param int<1, 10> $r1
	 * @param int<5, 10> $r2
	 * @param int<min, 5> $rMin
	 * @param int<5, max> $rMax
	 */
	public function math($i, $pi, $r1, $r2, $rMin, $rMax) {
		assertType('int', $r1 + $i);
		assertType('int', $r1 - $i);
		assertType('int', $r1 * $i);
		assertType('(float|int)', $r1 / $i);

		assertType('int<2, max>', $pi + 1);
		assertType('int<-1, max>', $pi - 2);
		assertType('int<2, max>', $pi * 2);
		assertType('int<0, max>', $pi / 2);
		assertType('int<2, max>', 1 + $pi);
		assertType('int<1, max>', 2 - $pi);
		assertType('int<2, max>', 2 * $pi);
		assertType('int<2, max>', 2 / $pi);

		assertType('int<5, 14>', $r1 + 4);
		assertType('int<-3, 6>', $r1 - 4);
		assertType('int<4, 40>', $r1 * 4);
		assertType('int<0, 2>', $r1 / 4);
		assertType('int<9, max>', $rMax + 4);
		assertType('int<1, max>', $rMax - 4);
		assertType('int<20, max>', $rMax * 4);
		assertType('int<1, max>', $rMax / 4);

		assertType('int<6, 20>', $r1 + $r2);
		assertType('int<-4, 0>', $r1 - $r2);
		assertType('int<5, 100>', $r1 * $r2);
		assertType('int<0, 1>', $r1 / $r2);

		assertType('int<min, 15>', $r1 + $rMin);
		assertType('int<min, 5>', $r1 - $rMin);
		assertType('int<min, 50>', $r1 * $rMin);
		assertType('int<min, 2>', $r1 / $rMin);
		assertType('int<min, 15>', $rMin + $r1);
		assertType('int<min, -5>', $rMin - $r1);
		assertType('int<min, 50>', $rMin * $r1);
		assertType('int<min, 0>', $rMin / $r1);

		assertType('int<6, max>', $r1 + $rMax);
		assertType('int<-4, max>', $r1 - $rMax);
		assertType('int<5, max>', $r1 * $rMax);
		assertType('int<0, max>', $r1 / $rMax);
		assertType('int<6, max>', $rMax + $r1);
		assertType('int<4, max>', $rMax - $r1);
		assertType('int<5, max>', $rMax * $r1);
		assertType('int<5, max>', $rMax / $r1);
	}
}
