<?php // lint >= 8.0

namespace NanToString;

use function PHPStan\Testing\assertType;

function doFoo(): void
{
	$nan = NAN;
	assertType("'NAN'", (string) $nan);
	assertType("'NAN'", $nan . '');
	assertType("'INF'", (string) INF);
	assertType("'-INF'", (string) -INF);
}
