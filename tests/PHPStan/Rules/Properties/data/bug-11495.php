<?php // lint >= 8.1
declare(strict_types = 1);

namespace Bug11495;

class HelloWorld
{
	private readonly string $foo;

	public function __construct()
	{
		$this->foo = 'bar';
	}

	public function __clone()
	{
		$this->foo = 'baz';
	}

	public function getFoo(): string
	{
		return $this->foo;
	}
}
