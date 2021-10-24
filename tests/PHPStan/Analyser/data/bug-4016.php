<?php

namespace Bug4016;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<int, int> $a
	 */
	public function doFoo(array $a): void
	{
		assertType('array<int, int>', $a);
		$a[] = 2;
		assertType('non-empty-array<int, int>', $a);

		unset($a[0]);
		assertType('array<int, int>', $a);
	}

	/**
	 * @param array<int, int> $a
	 */
	public function doBar(array $a): void
	{
		assertType('array<int, int>', $a);
		$a[1] = 2;
		assertType('non-empty-array<int, int>', $a);

		unset($a[1]);
		assertType('array<int, int>', $a);
	}

}
