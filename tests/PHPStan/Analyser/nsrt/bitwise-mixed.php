<?php

namespace BitwiseMixed;

use function PHPStan\Testing\assertType;

/**
 * @param mixed $mixed
 */
function doFoo(string $string, $mixed): void
{
	// only two strings make a string; '1' & 1 is 1
	assertType('string', $string & $string);
	assertType('(int|string)', $string & $mixed);
	assertType('(int|string)', $mixed | $string);
	assertType('(int|string)', '1' ^ $mixed);
	assertType('(int|string)', $mixed & $mixed);
}
