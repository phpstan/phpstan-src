<?php

namespace ForeachPartiallyNonIterable;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param array<string, int>|false $a
	 */
	public function doFoo($a): void
	{
		foreach ($a as $k => $v) {
			assertType('string', $k);
			assertType('int', $v);
		}
	}

}
