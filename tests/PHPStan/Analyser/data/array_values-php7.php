<?php // onlyif PHP_VERSION_ID < 80000

namespace ArrayValuesPhp7;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function foo1($mixed): void
	{
		if (is_array($mixed)) {
			assertType('list<mixed>', array_values($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('null', array_values($mixed));
		}
	}

}
