<?php

namespace ArrayValues;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<mixed>', array_values($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('*NEVER*', array_values($mixed));
		}
	}
}
