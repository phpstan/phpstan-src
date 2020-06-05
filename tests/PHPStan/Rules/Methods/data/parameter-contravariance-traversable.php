<?php

namespace ParameterContravarianceTraversable;

class Foo
{

	public function doFoo(\Traversable $a)
	{

	}

	public function doBar(?\Traversable $a)
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
