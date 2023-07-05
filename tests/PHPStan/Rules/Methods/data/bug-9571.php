<?php

namespace Bug9571;

trait Foo {
	public function __construct() {}
}

class HelloWorld
{
	use Foo {
		__construct as baseConstructor;
	}

	public function __construct()
	{
		$this->baseConstructor();
	}
}
