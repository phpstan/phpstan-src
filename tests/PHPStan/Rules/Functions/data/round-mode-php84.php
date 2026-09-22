<?php

namespace RoundModePhp84;

function doFoo(float $f): void
{
	$a = round($f, 0, PHP_ROUND_HALF_UP);
	$a = round($f, 0, 5);
	$a = round($f, 0, 8);
	$a = round($f, 0, \RoundingMode::HalfEven);
	$a = round($f, 0, 9);
	$a = round($f, 0, 0);
}
