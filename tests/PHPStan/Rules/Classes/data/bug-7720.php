<?php declare(strict_types = 1);

namespace Bug7720;

trait FooBar
{
	public function foo(): string
	{
		return 'abc';
	}
}

class HelloWorld
{
	public function sayHello(mixed $value): void
	{
		if ($value instanceof FooBar) {
			echo $value->foo();
		}
	}
}
