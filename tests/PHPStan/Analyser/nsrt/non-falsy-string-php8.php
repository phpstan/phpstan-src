<?php // lint >= 8.0

namespace NonFalseyStringPhp8;

use function PHPStan\Testing\assertType;

function doFoo()
{
	assertType("string", sprintf('%s')); // error
	assertType("string", vsprintf('%s')); // error
}
