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
		assertType('list<float|int>', range($a, $b));
	}

}
