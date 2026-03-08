<?php

namespace Bug13227;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Type1 object{ a: int }
 * @phpstan-type Type2 Type1 & object{ b: int }
 * @phpstan-type Type3 object{ a: int, b?: string } & object{ b: string, c?: int }
 */
class Foo
{
	/**
	 * @param Type2 $x
	 */
	public function doFoo($x): void
	{
		assertType('object{a: int, b: int}', $x);
	}

	/**
	 * @param Type3 $y
	 */
	public function doBar($y): void
	{
		assertType('object{a: int, b: string, c?: int}', $y);
	}
}
