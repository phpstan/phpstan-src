<?php

namespace Bug13232b;

final class HelloWorld
{
	public function sayHello(): void
	{
		if (rand(0, 1)) {
			$x = new Foo();
		} else {
			$x = '1';
		}
		echo 'Hello, ' . mightReturnNever($x)
			. ' no way';

		echo 'Hello, ' . mightReturnNever(new Foo())
			. ' no way';
		echo 'this will never happen';
	}
}

/** @phpstan-return ($x is Foo ? never : string) */
function mightReturnNever(mixed $x)
{
	if ($x instanceof Foo) {
		throw new LogicException();
	}
	return "hello";
}

class Foo
{
}
