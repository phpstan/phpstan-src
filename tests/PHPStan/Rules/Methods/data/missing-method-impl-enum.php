<?php

namespace MissingMethodImplEnum;

interface FooInterface
{

	public function doFoo(): void;

}

enum Foo implements FooInterface
{
	public function doFoo(): void
	{
		// TODO: Implement doFoo() method.
	}

}

enum Bar implements FooInterface
{

}
