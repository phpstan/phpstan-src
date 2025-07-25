<?php // lint >= 8.1

namespace Bug13297;

enum Foo: int {
	case A = 1;
	case B = 2;
}

class HelloWorld
{
	/** @param value-of<Foo> $int */
	public function sayHello(int $int): void
	{
		Foo::from($int);
	}

	public function sayHello2(): void
	{
		Foo::from(1);
	}

	public function sayHello3(int $int): void
	{
		Foo::from($int);
	}

	/** @param 1|2|3 $int */
	public function sayHello4(int $int): void
	{
		Foo::from($int);
	}
}
