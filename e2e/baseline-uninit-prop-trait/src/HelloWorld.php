<?php

namespace Readonly\Uninit\Bug;

class HelloWorld
{
	use Foo;

	public function __construct()
	{
		$this->init();
		$this->foo();
	}
}
