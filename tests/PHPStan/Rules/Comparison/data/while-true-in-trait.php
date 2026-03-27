<?php

namespace WhileTrueInTrait;

trait FooTrait
{

	public function doFoo(): void
	{
		// sometimes truthy, sometimes not
		while ($this->doBar()) {

		}
	}

	public function doFoo2(): void
	{
		// always truthy
		while ($this->doBar2()) {

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
