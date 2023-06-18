<?php declare(strict_types = 1);

namespace Bug9459;

class HelloWorld
{
	public function sayHello(): callable
	{
		/** @var callable(): mixed[] */
		return function (): array { return []; };
	}
}
