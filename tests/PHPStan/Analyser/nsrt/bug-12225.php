<?php declare(strict_types = 1);

namespace Bug12225;

use function PHPStan\Testing\assertType;

function floatAsserts(float $num): ?int
{
	if ($num === 0.0) {
		assertType('0.0', $num);
		assertType("'-0'|'0'", (string) $num);
	}

	if ($num == 0) {
		assertType('0.0', $num);
		assertType("'-0'|'0'", (string) $num);
	}

	assertType("'-0'|'0'", (string) 0.0); // could be '0'
	assertType("'-0'|'0'", (string) -0.0); // could be '-0'

	assertType('-0.0', -1.0 * 0.0);
	assertType('0.0', 1.0 * 0.0);
	assertType('0.0', -1.0 * -0.0);
	assertType('-0.0', 1.0 * -0.0);

	assertType('true', 0.0 === -0.0);
	assertType('true', 0.0 == -0.0);

	return null;
}

/** @param mixed $num */
function withMixed($num): ?int
{
	if ($num === 0.0) {
		assertType('0.0', $num);
		assertType("'-0'|'0'", (string) $num);
	}

	assertType('true', 0.0 === -0.0);

	return null;
}
