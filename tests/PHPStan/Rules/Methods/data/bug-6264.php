<?php declare(strict_types = 1);

namespace Bug6264;

interface Foo
{
	public function doFoo(): void;
}

class Bar implements Foo
{
	public function doFoo(): void
	{
		echo "Doing foo the generic way";
	}
}

trait SpecificFoo
{
	public function doFoo(): void
	{
		echo "Doing foo the specific way";
	}
}

class Baz extends Bar
{
	use SpecificFoo {
		doFoo as private doFooImpl;
	}

	public function doFoo(): void
	{
		echo "Doing foo twice";
		$this->doFooImpl();
	}
}

class FooBar extends Bar
{
	use SpecificFoo;
}
