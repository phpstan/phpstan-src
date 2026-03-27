<?php

namespace BooleanOrInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// left side: sometimes constant, sometimes not
		if ($this->doBar() || rand(0, 1)) {

		}
	}

	public function doFoo2()
	{
		// left side: always constant
		if ($this->doBar2() || rand(0, 1)) {

		}
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): \stdClass
	{

	}

	public function doBar2(): \stdClass
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): ?\stdClass
	{

	}

	public function doBar2(): \stdClass
	{

	}

}
