<?php // lint >= 8.0

namespace NonFalseyString74OrNewer;

use function PHPStan\Testing\assertType;

function doFoo()
{
	assertType("string", sprintf('%s')); // error
	assertType("string", vsprintf('%s')); // error
}
