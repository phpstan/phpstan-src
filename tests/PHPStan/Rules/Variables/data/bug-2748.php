<?php

namespace Bug2748;

class Foo
{

	public function doBar()
	{
		$foo->bar = 'test2';
	}

	public function doBaz()
	{
		$foo::$bar = 'test2';
	}

	public function doLorem(string $foo)
	{
		$foo::$bar = 'test3';
	}

}
