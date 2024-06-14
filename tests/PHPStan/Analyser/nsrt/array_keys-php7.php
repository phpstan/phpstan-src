<?php // lint < 8.0

namespace ArrayKeysPhp7;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function sayHello($mixed): void
	{
		if (is_array($mixed)) {
			assertType('list<(int|string)>', array_keys($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('null', array_keys($mixed));
		}
	}

}
