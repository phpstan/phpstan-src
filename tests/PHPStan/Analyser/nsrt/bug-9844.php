<?php declare(strict_types = 1);

namespace Bug9844;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param class-string $class
	 */
	public function sayHello(string $class): void
	{
		assertType('mixed', $class::foo());
		assertType('mixed', $class->foo());
		assertType('mixed', $class?->foo());
	}
}
