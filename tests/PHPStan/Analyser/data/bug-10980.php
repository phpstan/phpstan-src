<?php

declare(strict_types=1);

namespace Bug10980;

use function PHPStan\Testing\assertType;

class A {}

class B extends A {}

function a(): A {}

while (true) {
	$type = a();
	if (!$type instanceof B) {
		continue;
	}
	break;
}

assertType('Bug10980\B', $type);
