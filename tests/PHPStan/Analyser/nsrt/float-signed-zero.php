<?php declare(strict_types = 1);

namespace FloatSignedZero;

use function PHPStan\Testing\assertType;

// 0.0 === -0.0, so PHPStan does not tell the zeros apart, but a negative odd exponent does:
// 0.0 ** -1 is INF and (-0.0) ** -1 is -INF
function power(float $f): void
{
	if ($f === 0.0) {
		assertType('-INF|INF', $f ** -1);
		assertType('INF', $f ** -2);
		assertType('0.0', $f ** 3);
	}

	assertType('-INF|INF', 0.0 ** -1);
	assertType('-INF|INF', (-0.0) ** -1);
	assertType('-INF|INF', 0.0 ** -3.0);
	assertType('INF', 0.0 ** -2.5);
	assertType('1.0', 0.0 ** 0);
}

// (string) -0.0 is '-0'
function stringify(float $f): void
{
	if ($f === 0.0) {
		assertType("'-0'|'0'", sprintf('%s', $f));
		assertType("'-0'|'0'", sprintf('%g', $f));
		assertType("'0.000000'", sprintf('%f', $f));
		assertType("'-0'|'0'", implode(',', [$f]));
		assertType("'-0,1'|'0,1'", implode(',', [$f, 1]));
	}

	assertType("'-0'|'0'", sprintf('%s', -0.0));
	assertType("'-0'|'0'", implode('', [0.0]));
}
