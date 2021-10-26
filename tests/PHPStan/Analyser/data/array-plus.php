<?php

namespace ArrayPlus;

use function PHPStan\Testing\assertType;

class Foo
{
	/**
	 * @param non-empty-array $s2
	 */
	public function doFoo(array $s1, $s2)
	{
		assertType('non-empty-array', $s1 + $s2);
		assertType('non-empty-array', $s2 + $s1);
		assertType('non-empty-array', $s2 + $s2);

		assertType('array', $s1 + $s1);
	}
}
