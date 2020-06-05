<?php

namespace ParameterContravarianceArray;

class Foo
{

	public function doFoo(array $a)
	{

	}

	public function doBar(?array $a)
	{

	}

}

class Bar extends Foo
{

	public function doFoo(iterable $a)
	{

	}

	public function doBar(?iterable $a)
	{

	}

}

class Baz extends Foo
{

	public function doFoo(?iterable $a)
	{

	}

	public function doBar(iterable $a)
	{

	}

}
