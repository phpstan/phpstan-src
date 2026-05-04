<?php

namespace Bug12517;

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

		$a = $foo->a;
		$b = $foo->b;
		if ($a !== null || $b !== null) {
			if ($a === null) {
				assertType('null', $a);
				assertType('mixed~null', $b);
			}
		}
	}
}
