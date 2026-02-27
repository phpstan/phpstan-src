<?php declare(strict_types = 1);

namespace Bug5865;

class HelloWorld
{
	public function sayHello(): void
	{
		try {
			do {
				$this->foo();
			} while (true);
		} catch (\RuntimeException $e) {
			// ok
		}
	}

	/**
	 * @throws \RuntimeException
	 */
	public function foo(): void
	{
		throw new \RuntimeException();
	}
}
