<?php

namespace Bug5872;

class HelloWorld
{
	/**
	 * @phpstan-param mixed[] $mixedArray
	 */
	public function sayHello(array $mixedArray): void
	{
		var_dump(array_map('\strval', $mixedArray["api"]));
		var_dump(array_map('\strval', (array) $mixedArray["api"]));
	}
}
