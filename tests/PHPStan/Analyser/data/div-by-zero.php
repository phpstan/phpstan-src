<?php

namespace DivByZero;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int<0, max> $range1
	 * @param int<min, 0> $range2
	 */
	public function doFoo(int $range1, int $range2): void
	{
		assertType('float|int', 5 / $range1);
		assertType('float|int', 5 / $range2);
		assertType('*ERROR*', 5 / 0);
	}

}
