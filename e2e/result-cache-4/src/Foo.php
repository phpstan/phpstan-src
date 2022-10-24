<?php

namespace TestResultCache4;

class Foo
{

	/** @var Bar */
	public \Exception $foo;

	public function doFoo(): mixed
	{
		return $this->foo;
	}

}
