<?php

namespace Bug10863;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param __benevolent<int|false> $b
	 */
	public function doFoo($b): void
	{
		assertType('non-falsy-string', '@' . $b);
	}

	/**
	 * @param int|false $b
	 */
	public function doFoo2($b): void
	{
		assertType('non-falsy-string', '@' . $b);
	}

}
