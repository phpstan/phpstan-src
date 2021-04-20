<?php

namespace CatchWithoutVariable;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		try {

		} catch (\FooException) {
			assertType('*ERROR*', $e);
		}
	}

}
