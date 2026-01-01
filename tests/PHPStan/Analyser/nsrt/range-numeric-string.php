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
		assertType('non-empty-list<float|int>', range($a, $b));
	}

}
