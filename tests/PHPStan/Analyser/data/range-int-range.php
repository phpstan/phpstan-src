<?php

namespace RangeNumericString;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int<0,max> $a
	 * @param int<0,max> $b
	 */
	public function doFoo(
		int $a,
		int $b
	): void
	{
		assertType('list<int<0, max>>', range($a, $b));
	}

}
