<?php // onlyif PHP_VERSION_ID < 80000

namespace ArrayFlipPhp7;

use function PHPStan\Testing\assertType;

function mixedAndSubtractedArray($mixed)
{
	if (is_array($mixed)) {
		assertType('array<int|string, (int|string)>', array_flip($mixed));
	} else {
		assertType('mixed~array', $mixed);
		assertType('null', array_flip($mixed));
	}
}
