<?php declare(strict_types = 1);

namespace Bug9155;

use function PHPStan\Testing\assertType;

class Foo
{
	public function fooF(): bool
	{
		return true;
	}
}

class Bar
{
	public function barF(): bool
	{
		return false;
	}
}

class HelloWorld
{
	private ?Foo $foo = null;
	private ?Bar $bar = null;

	public function test(): void
	{
		if (null === $this->foo && null === $this->bar) {
			return;
		}

		if (null === $this->foo) {
			assertType('Bug9155\Bar', $this->bar);
		}
	}
}
