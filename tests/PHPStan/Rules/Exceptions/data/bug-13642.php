<?php declare(strict_types = 1);

namespace Bug13642;

class HelloWorld
{
	/** @throws void */
	public function sayHello(): void
	{
		array_combine([1, 2], [1, 2]);
	}
}
