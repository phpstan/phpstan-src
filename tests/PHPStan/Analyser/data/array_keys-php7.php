<?php // onlyif PHP_VERSION_ID < 80000

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
