<?php

namespace ReturnTypeCovariance;

class Foo
{

	public function doFoo(): iterable
	{

	}

	public function doBar(): array
	{

	}

	public function doBaz(): \Exception
	{

	}

	public function doLorem(): \InvalidArgumentException
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): array
	{

	}

	public function doBar(): iterable
	{

	}

	public function doBaz(): \InvalidArgumentException
	{

	}

	public function doLorem(): \Exception
	{

	}

}

class A
{

	public function foo(string $s): ?\stdClass
	{

	}
}
class B extends A
{

	public function foo($s)
	{
		return rand(0, 1) ? new stdClass : null;
	}

}
