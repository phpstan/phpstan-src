<?php

namespace TestStringables;

use Stringable;

class Foo
{

	public function __toString(): string
	{
		return 'foo';
	}

}

class Bar implements Stringable
{

	public function __toString(): string
	{
		return 'foo';
	}

}

interface Lorem extends Stringable
{

}

class Baz
{

	public function doFoo(Stringable $s): void
	{

	}

	public function doBar(Lorem $l): void
	{
		$this->doFoo(new Foo());
		$this->doFoo(new Bar());
		$this->doFoo($l);
		$this->doBaz($l);
	}

	public function doBaz(string $s): void
	{

	}

}
