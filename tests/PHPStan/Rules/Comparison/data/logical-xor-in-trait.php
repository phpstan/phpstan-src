<?php

namespace LogicalXorInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// left side: sometimes constant, sometimes not
		if ($this->doBar() xor rand(0, 1)) {

		}
	}

	public function doFoo2()
	{
		// left side: always constant (always false)
		if ($this->doBar2() xor rand(0, 1)) {

		}
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
