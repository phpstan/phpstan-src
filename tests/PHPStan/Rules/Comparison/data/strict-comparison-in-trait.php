<?php

namespace StrictComparisonInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes nullable, sometimes not
		if ($this->doBar() !== null) {

		}
	}

	public function doFoo2()
	{
		// always not nullable
		if ($this->doBar2() !== null) {

		}
	}

}

class Foo
{

	 use FooTrait;

	public function doBar(): string
	{

	}

	public function doBar2(): string
	{

	}

}

class FooAnother
{

	use FooTrait;

	public function doBar(): ?string
	{

	}

	public function doBar2(): string
	{

	}

}
