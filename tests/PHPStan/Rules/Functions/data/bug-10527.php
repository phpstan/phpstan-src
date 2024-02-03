<?php declare(strict_types=1);

namespace Bug10527;

class HelloWorld
{
	/**
	 * @param array{0: list<string>, 1: list<string>} $tuple
	 */
	public function sayHello(array $tuple): void
	{
		array_map(fn (string $first, string $second) => $first . $second, ...$tuple);
	}
}
