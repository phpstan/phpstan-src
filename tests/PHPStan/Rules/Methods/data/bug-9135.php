<?php declare(strict_types = 1);

namespace Bug9135;

class HelloWorld
{
	/** @final */
	public function sayHello(): void
	{
		echo 'Hello';
	}
}

class Sub extends HelloWorld {
	public function sayHello(): void
	{
		echo 'Hello';
	}
}
