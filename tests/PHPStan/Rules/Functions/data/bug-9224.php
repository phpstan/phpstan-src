<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug3425;

class HelloWorld
{
	/** @param array<int> $arr */
	public function sayHello(array $arr): void
	{
		array_map(abs(...), $arr);
	}
}
