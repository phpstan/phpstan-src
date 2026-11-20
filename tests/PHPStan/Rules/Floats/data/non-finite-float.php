<?php declare(strict_types = 1);

namespace NonFiniteFloat;

/**
 * @param NAN $nan
 * @param INF $inf
 */
function issueExample(float $nan, float $inf, float $plain): void
{
	echo json_encode($nan);
	echo $nan;
	$i = (int) $inf;
	echo sprintf('%f', $nan);

	echo $plain;
	if (is_nan($plain)) {
		echo $plain;
	}
}

/**
 * @param finite-float $finite
 * @param float<0, max> $nonNegative
 * @param NAN|float<0, 1> $nanOrUnit
 * @param list<float> $floats
 * @param list<finite-float> $finiteFloats
 * @param array{a: NAN} $nanShape
 * @param int|string $noFloat
 */
function jsonEncode(float $finite, float $nonNegative, float $nanOrUnit, array $floats, array $finiteFloats, array $nanShape, $noFloat, $mixed): void
{
	json_encode($finite);
	json_encode($nonNegative);
	json_encode($nanOrUnit);
	json_encode($floats);
	json_encode($finiteFloats);
	json_encode($nanShape);
	json_encode($noFloat);
	json_encode($mixed);
}

/**
 * @param NAN $nan
 * @param INF $inf
 * @param finite-float $finite
 */
function toString(float $nan, float $inf, float $finite, float $plain): void
{
	print $nan;
	$s = 'x' . $nan;
	$s .= $plain;
	$s = "value: {$nan}";
	$s = (string) $nan;
	echo $inf, $finite;
	if (is_finite($plain)) {
		echo $plain;
	}
}

/**
 * @param NAN $nan
 * @param finite-float $finite
 * @param float<-1000.0, 1000.0> $small
 */
function toInt(float $nan, float $finite, float $small, float $plain): void
{
	$i = (int) $nan;
	$i = intval($nan);
	$i = (int) $small;
	$i = (int) $finite;
	$i = (int) $plain;
	$i = intval('12', 8);
}

/**
 * @param NAN $nan
 * @param INF $inf
 * @param finite-float $finite
 */
function format(float $nan, float $inf, float $finite, float $plain): void
{
	echo number_format($nan);
	echo number_format($finite);
	echo sprintf('%.2f %d %s', $inf, $inf, $inf);
	echo sprintf('%e %s', $nan, $nan);
	echo sprintf('%1$f %1$f', $plain);
	printf('%d', $finite);
	fprintf(STDOUT, '%F', $plain);
}
