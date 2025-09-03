<?php declare(strict_types = 1);

namespace Bug11609;

class HelloWorld
{
	/** @param object{hello: string, world?: string} $a */
	public function sayHello(object $a, string $b): void
	{
		if ($a->hello !== null) { // should report notIdentical.alwaysTrue
			echo 'hello';
		}
		if (isset($a->world)) { // shouldn't report any error
			echo 'world';
		}
		if ($b !== null) {
			echo 'b';
		}
	}
}
