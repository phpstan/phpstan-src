<?php // lint >= 8.2

namespace ElseIfConditionInTrait;

trait FooTrait
{

	public function doFoo()
	{
		$x = rand(0, 1);
		// sometimes falsy, sometimes not
		if ($x) {
		} elseif ($this->doBar()) {

		}
	}

	public function doFoo2()
	{
		$x = rand(0, 1);
		// always falsy
		if ($x) {
		} elseif ($this->doBar2()) {

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
