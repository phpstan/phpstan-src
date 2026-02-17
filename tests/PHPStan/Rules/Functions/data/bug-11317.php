<?php declare(strict_types = 1);

namespace Bug11317;

class HelloWorld
{
	/**
	 * @param array<int> $a
	 */
	public function sayHello(array $a): void
	{
		array_map(function ($i) {
			return $i;
		}, $a);
	}
}
