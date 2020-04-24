<?php

namespace TestAccessProperties;

class Foo
{
	public function foo($foo)
	{
		$foo->loremipsum;
	}

	/**
	 * @param mixed $foo
	 */
	public function foo2($foo)
	{
		$foo->loremipsum;
	}
}
