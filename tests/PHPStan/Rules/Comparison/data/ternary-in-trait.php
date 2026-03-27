<?php

namespace TernaryInTrait;

trait FooTrait
{

	public function doFoo()
	{
		// sometimes truthy, sometimes not
		$x = $this->doBar() ? 'yes' : 'no';
	}

	public function doFoo2()
	{
		// always truthy
		$x = $this->doBar2() ? 'yes' : 'no';
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
