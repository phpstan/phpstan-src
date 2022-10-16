<?php

namespace ArrayKeys;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<(int|string)>', array_keys($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('*ERROR*', array_keys($mixed));
		}
	}
}
