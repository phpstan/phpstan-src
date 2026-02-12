<?php declare(strict_types = 1);

namespace Bug12517;

use stdClass;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(stdClass $foo): void
	{
		if ($foo->a !== null || $foo->b !== null) {
			if ($foo->a === null) {
				assertType('null', $foo->a);
				assertType('mixed~null', $foo->b);
			}
		}
	}
}
