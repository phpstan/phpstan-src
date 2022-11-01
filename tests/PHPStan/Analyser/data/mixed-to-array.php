<?php

namespace MixedToArray;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if (!is_array($mixed)) {
			assertType('mixed~array', $mixed);
			assertType('*NEVER*', (array) $mixed);
		}
	}
}
