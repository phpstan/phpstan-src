<?php

namespace Bug5867;

class HelloWorld
{
	/**
	 * @param class-string $factory
	 */
	public function sayHello(string $factory): void
	{
		if (class_exists($factory) && method_exists($factory, '__invoke')) {
			(new $factory())();
		}
	}
}
