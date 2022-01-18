<?php

namespace Bug5698;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class FooPHP8
{

	function foo(int ...$foo): void {
		assertType('array<int|string, int>', $foo);
		assertNativeType('array<int|string, int>', $foo);
	}

}
