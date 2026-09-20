<?php // lint < 8.0

namespace ArrayFillKeysPhp7;

use function PHPStan\Testing\assertType;

function mixedAndSubtractedArray($mixed): void
{
	if (is_array($mixed)) {
		assertType("array<'b'>", array_fill_keys($mixed, 'b'));
	} else {
		assertType("null", array_fill_keys($mixed, 'b'));
	}
}
