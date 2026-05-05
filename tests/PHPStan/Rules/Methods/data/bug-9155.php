<?php

namespace Bug9155;

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

		if (null === $this->foo && !$this->bar->barF()) {
			echo 1;
		}
	}
}
