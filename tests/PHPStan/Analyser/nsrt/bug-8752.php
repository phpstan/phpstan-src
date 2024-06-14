<?php

namespace Bug8752;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param class-string&literal-string $s
	 */
	public function sayHello(string $s): void
	{
		if (method_exists($s, 'abc')) {
			assertType('class-string&hasMethod(abc)&literal-string', $s);

			$s::abc();
			$s->abc();
		}
	}
}
