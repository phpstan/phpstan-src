<?php

namespace NarrowsSuperGlobal;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function doFoo(): void
	{
		if (array_key_exists('HTTP_HOST', $_SERVER)) {
			assertType("non-empty-array&hasOffset('HTTP_HOST')", $_SERVER);
			echo $_SERVER['HTTP_HOST'];
		}
	}
}
