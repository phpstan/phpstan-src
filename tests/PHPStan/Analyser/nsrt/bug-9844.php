<?php declare(strict_types = 1);

namespace Bug9844;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param class-string $class
	 */
	public function sayHello(string $class, string $method, string $property): void
	{
		assertType('mixed', $class::foo());
		assertType('*ERROR*', $class->foo());
		assertType('*ERROR*', $class?->foo());
		assertType('mixed', $class::$method());
		assertType('mixed', $class->$property);
		assertType('mixed', $class?->$property);
	}
}
