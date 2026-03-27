<?php

namespace IfConditionInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes truthy, sometimes not
		if ($this->doBar()) {

		}
	}

	public function doFoo2()
	{
		// always truthy
		if ($this->doBar2()) {

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
