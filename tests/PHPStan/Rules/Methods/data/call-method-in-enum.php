<?php // lint >= 8.1

namespace CallMethodInEnum;

enum Foo
{

	public function doFoo()
	{
		$this->doFoo();
		$this->doNonexistent();
	}

}

trait FooTrait
{

	public function doFoo()
	{
		$this->doFoo();
		$this->doNonexistent();
	}

}

enum Bar
{

	use FooTrait;

}
