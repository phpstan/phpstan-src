<?php

declare(strict_types = 1);

namespace Bug8864;

use function PHPStan\Testing\assertType;

/**
 * @param array{0: 1, 1?: 2} $a
 */
function test(array $a): void
{
	if (in_array(2, $a, true)) {
		assertType('array{0: 1, 1?: 2}', $a);
	}

	// TODO: value type of optional key should not be narrowed to *NEVER*
	if (!in_array(2, $a, true)) {
		assertType('array{0: 1, 1?: *NEVER*}', $a);
	}
}

/**
 * @param 1|2 $x
 * @param array{0: 1, 1?: 2} $a
 */
function testNeedle($x, array $a): void
{
	if (in_array($x, $a, true)) {
		assertType('1|2', $x);
	}

	if (!in_array($x, $a, true)) {
		// 1 is guaranteed in the array, so if not in_array, x must be 2
		assertType('2', $x);
	}
}
