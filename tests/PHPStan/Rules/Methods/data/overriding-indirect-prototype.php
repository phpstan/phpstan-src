<?php // lint >= 8.0

namespace OverridingIndirectPrototype;

class Foo
{

	public function doFoo(): mixed
	{

	}

}

class Bar extends Foo
{

	public function doFoo(): string
	{

	}

}

class Baz extends Bar
{

	public function doFoo(): mixed
	{

	}

}
