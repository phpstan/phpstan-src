<?php

namespace Bug5089;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @return never
	 */
	public function encode(string $foo): array
	{
		throw new \BadMethodCallException();
	}

	public function test(): void
	{
		assertType('*NEVER*', $this->encode('foo'));
	}
}
