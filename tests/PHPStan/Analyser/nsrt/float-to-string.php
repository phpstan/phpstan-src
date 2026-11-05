<?php

namespace FloatToString;

use function PHPStan\Testing\assertType;

/**
 * @param int|float $number
 */
function doFoo(float $float, $number): void
{
	// INF, -INF and NAN stringify as 'INF', '-INF' and 'NAN', which are not numeric
	assertType('non-empty-string&uppercase-string', (string) $float);
	assertType('non-empty-string&uppercase-string', '' . $float);
	assertType('non-falsy-string&uppercase-string', $float . '1');
	assertType('non-empty-string&uppercase-string', (string) $number);

	// constant floats stay exact
	assertType("'1.5'", (string) 1.5);
	assertType("'INF'", (string) INF);
}
