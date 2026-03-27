<?php

namespace ImpossibleFunctionCallInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes constant, sometimes not
		if (is_string($this->doBar())) {

		}
	}

	public function doFoo2()
	{
		// always false
		if (is_string($this->doBar2())) {

		}
	}

}

class Foo
{

	use FooTrait;

	public function doBar(): int
	{

	}

	public function doBar2(): int
	{

	}

}

class FooAnother
{

	use FooTrait;

	/** @return int|string */
	public function doBar()
	{

	}

	public function doBar2(): int
	{

	}

}
