<?php

namespace StaticClosure;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		static function () {
			assertType('*ERROR*', $this);
		};
	}

}
