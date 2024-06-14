<?php

namespace Bug5309;

use function PHPStan\Testing\assertType;

function greater(float $y): float {
	$x = 0.0;
	if($y > 0) {
		$x += 1;
	}
	assertType('0.0|1.0', $x);
	if($x > 0) {
		assertType('1.0', $x);
		return 5 / $x;
	}
	assertType('0.0|1.0', $x); // could be '0.0' when we support float-ranges

	return 1.0;
}

function greaterEqual(float $y): float {
	$x = 0.0;
	if($y > 0) {
		$x += 1;
	}
	assertType('0.0|1.0', $x);
	if($x >= 0) {
		assertType('0.0|1.0', $x);
		return 5 / $x;
	}
	assertType('0.0|1.0', $x); // could be '*NEVER*' when we support float-ranges

	return 1.0;
}

function smaller(float $y): float {
	$x = 0.0;
	if($y > 0) {
		$x -= 1;
	}
	assertType('-1.0|0.0', $x);
	if($x < 0) {
		assertType('-1.0', $x);
		return 5 / $x;
	}
	assertType('-1.0|0.0', $x); // could be '0.0' when we support float-ranges

	return 1.0;
}

function smallerEqual(float $y): float {
	$x = 0.0;
	if($y > 0) {
		$x -= 1;
	}
	assertType('-1.0|0.0', $x);
	if($x <= 0) {
		assertType('-1.0|0.0', $x);
		return 5 / $x;
	}
	assertType('*NEVER*', $x);

	return 1.0;
}
