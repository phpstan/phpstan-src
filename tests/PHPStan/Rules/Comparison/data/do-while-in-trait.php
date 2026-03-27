<?php // lint >= 8.2

namespace DoWhileInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		do {
		} while ($this->doBar());
	}

	public function doFoo2()
	{
		// always falsy
		do {
		} while ($this->doBar2());
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): null
	{

	}

	public function doBar2(): null
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): ?\stdClass
	{

	}

	public function doBar2(): null
	{

	}

}
