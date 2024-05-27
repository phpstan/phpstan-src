<?php declare(strict_types=1);

namespace Bug5309;

function sayHello(float $y): float
{
	$x = 0.0;
	if ($y > 0) {
		$x += 1;
	}
	if ($x > 0) {
		return 5 / $x;
	}

	return 1.0;
}

