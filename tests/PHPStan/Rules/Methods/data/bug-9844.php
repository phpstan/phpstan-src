<?php declare(strict_types = 1);

namespace Bug9844;

class HelloWorld
{

	/**
	 * @param class-string $class
	 */
	public function sayHello(string $class): void
	{
		$class::foo();
	}

	/**
	 * @param object $class
	 */
	public function sayHello2(object $class): void
	{
		$class::foo();
	}
}
