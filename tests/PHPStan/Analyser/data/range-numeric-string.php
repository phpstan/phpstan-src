<?php

namespace RangeNumericString;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param numeric-string $a
	 * @param numeric-string $b
	 */
	public function doFoo(
		string $a,
		string $b
	): void
	{
		assertType('array<int, float|int>', range($a, $b));
	}

}
