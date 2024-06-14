<?php

namespace PureCallable;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param pure-callable $cb
	 */
	public function doFoo(callable $cb): void
	{
		assertType('pure-callable(): mixed', $cb);
	}

}
