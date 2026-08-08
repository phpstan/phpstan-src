<?php declare(strict_types = 1);

namespace Bug14416;

trait MyTrait
{
	function myMethod(): bool
	{
		return isset($this->i);
	}
}

class MyClass
{
	use MyTrait;

	public int $i = 10;
}

class MyClass2
{
	use MyTrait;
}
