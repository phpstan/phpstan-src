<?php

namespace Bug4455Constructor;

class HelloWorld
{
	public function sayHello(string $_): bool
	{
		new self();
	}

	/**
	 * @psalm-pure
	 * @return never
	 */
	public function __construct() {
		throw new \Exception();
	}
}
