<?php declare(strict_types = 1);

namespace FloatRangeArgument;

/** @param float<0, 1> $x */
function unit(float $x): void
{
}

/** @param float<min, max> $x */
function notNan(float $x): void
{
}

function doFoo(float $f, int $i): void
{
	unit($f);
	unit(5.0);
	unit(NAN);
	unit($i);
	unit(2);
	unit(1);
	unit(0.5);

	notNan($f);
	notNan(NAN);
	notNan($i);
}
