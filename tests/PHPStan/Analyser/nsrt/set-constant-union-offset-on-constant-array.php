<?php

namespace SetConstantUnionOffsetOnConstantArray;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array{foo: int}  $a
	 */
	public function doFoo(array $a): void
	{
		$k = rand(0, 1) ? 'a' : 'b';
		$a[$k] = 256;
		assertType('array{foo: int, a?: 256, b?: 256}', $a);
	}

}
