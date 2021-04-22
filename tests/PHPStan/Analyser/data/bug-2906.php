<?php

namespace Bug2906;


use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param string $thing
	 * @param mixed $value
	 */
	public function sayHello($thing, $value): void
	{
		assertType('false', $thing === null);
		assertNativeType('bool', $thing === null);
	}
}
