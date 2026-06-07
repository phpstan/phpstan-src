<?php declare(strict_types = 1);

namespace Bug14792;

use function PHPStan\Testing\assertType;

function test(string $x): bool
{
	if (ctype_digit($x)) {
		// '02' passes ctype_digit() but is not a decimal-int-string,
		// so $x must not be narrowed to decimal-int-string.
		assertType('numeric-string', $x);
		if ($x === '02') { // absolutely possible
			return true;
		}
	}
	return false;
}
