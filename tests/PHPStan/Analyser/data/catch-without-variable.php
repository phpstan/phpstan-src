<?php

namespace CatchWithoutVariable;

use function PHPStan\Testing\assertType;

interface I
{
	function test(): void;
}

class Foo
{

	public function doFoo(I $i): void
	{
		try {
			$i->test();
		} catch (\FooException) {
			assertType('*ERROR*', $e);
		}
	}

}
