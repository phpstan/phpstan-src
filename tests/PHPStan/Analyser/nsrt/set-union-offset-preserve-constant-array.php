<?php

namespace SetUnionOffsetPreserveConstantArray;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$a = [];

		$k = rand(0, 1) ? 1 : 2;
		$a[$k] = true;
		assertType('array{1?: true, 2?: true}', $a);
	}

}
