<?php

namespace ArrayDestructuringTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var int */
	private $foo;

	public function doFoo()
	{
		[$this->foo] = [1];
		assertType('1', $this->foo);
	}

	public function doBar()
	{
		foreach ([1, 2, 3] as $this->foo) {
			//assertType('1|2|3', $this->foo);
		}
	}

	public function doBaz()
	{
		foreach ([[1], [2], [3]] as [$this->foo]) {
			assertType('1|2|3', $this->foo);
		}
	}

}
