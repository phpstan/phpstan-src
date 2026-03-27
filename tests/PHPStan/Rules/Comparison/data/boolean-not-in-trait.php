<?php

namespace BooleanNotInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		if (!$this->doBar()) {

		}
	}

	public function doFoo2()
	{
		// always constant (negation of always-truthy is always false)
		if (!$this->doBar2()) {

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
