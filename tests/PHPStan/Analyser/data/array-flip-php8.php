<?php // onlyif PHP_VERSION_ID >= 80000

namespace ArrayFlipPhp8;

use function PHPStan\Testing\assertType;

function mixedAndSubtractedArray($mixed)
{
	if (is_array($mixed)) {
		assertType('array<int|string, (int|string)>', array_flip($mixed));
	} else {
		assertType('mixed~array', $mixed);
		assertType('*NEVER*', array_flip($mixed));
	}
}
