<?php

namespace Bug5698;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class FooPHP7
{

	function foo(int ...$foo): void {
		assertType('array<int, int>', $foo);
		assertNativeType('array<int, int>', $foo);
	}

}
