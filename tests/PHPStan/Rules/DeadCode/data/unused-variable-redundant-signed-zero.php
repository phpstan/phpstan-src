<?php declare(strict_types = 1);

namespace UnusedVariableRedundantSignedZero;

function f(float $f): float {
	if ($f === 0.0) {
		$f = 0.0;
	}
	return $f;
}
function g(): float {
	$x = -0.0;
	$x = 0.0;
	return $x;
}

/** @param mixed $v */
function sink($v): void
{
}

function negativeZeroNarrowing(float $f): void
{
	if ($f === -0.0) {
		$f = -0.0;
	}
	sink($f);
}

function looseZeroNarrowing(float $f): void
{
	if ($f == 0) {
		$f = 0.0;
	}
	sink($f);
}

function zeroInArray(): void
{
	$a = [-0.0];
	sink($a);
	$a = [0.0];
	sink($a);
}

function zeroAtOffset(): void
{
	$a = ['k' => -0.0];
	sink($a);
	$a['k'] = 0.0;
	sink($a);
}

function nonZeroFloatIsStillRedundant(): void
{
	$x = 1.5;
	sink($x);
	$x = 1.5;
	sink($x);
}
