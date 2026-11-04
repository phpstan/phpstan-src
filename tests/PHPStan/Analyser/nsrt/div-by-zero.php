<?php

namespace DivByZero;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param int<0, max> $range1
	 * @param int<min, 0> $range2
	 */
	public function doFoo(int $range1, int $range2, int $int): void
	{

		assertType('*ERROR*', 5 / 0);
		assertType('*ERROR*', 5 / '0');
		assertType('*ERROR*', 5 / 0.0);
		assertType('*ERROR*', 5 / false);
		assertType('*ERROR*', 5 / null);
	}

}
