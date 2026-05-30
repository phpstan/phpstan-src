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

		$s = new self();
		$s->foo = 'baz';
	}

	public function getFoo(): string
	{
		return $this->foo;
	}
}

class DoubleAssign
{
	private readonly string $foo;

	public function __construct()
	{
		$this->foo = 'bar';
	}

	public function __clone()
	{
		$this->foo = 'baz';
		$this->foo = 'qux';
	}
}

class BranchedAssign
{
	private readonly string $foo;

	public function __construct()
	{
		$this->foo = 'bar';
	}

	public function __clone()
	{
		if (rand(0, 1)) {
			$this->foo = 'a';
		} else {
			$this->foo = 'b';
		}
	}
}

class ConditionalThenAssign
{
	private readonly string $foo;

	public function __construct()
	{
		$this->foo = 'bar';
	}

	public function __clone()
	{
		if (rand(0, 1)) {
			$this->foo = 'a';
		}
		$this->foo = 'b';
	}
}
