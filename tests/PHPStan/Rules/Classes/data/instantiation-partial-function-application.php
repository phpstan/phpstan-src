<?php // lint >= 8.6

namespace InstantiationPartialFunctionApplication;

class Foo
{

	public function __construct(int $a, string $b)
	{
	}

	public function doFoo(): void
	{
		$f = new self(?, ?);
		$g = new self(1, ...);
	}

}
