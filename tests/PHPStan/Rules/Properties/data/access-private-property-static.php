<?php

namespace AccessPrivatePropertyThroughStatic;

class Foo
{

	private static $foo;
	private $bar;

	public function doBar()
	{
		static::$foo;
		static::$bar; // reported by different rule
		static::$nonexistent; // reported by different rule
	}

}

final class Bar
{

	private static $foo;
	private $bar;

	public function doBar()
	{
		static::$foo;
		static::$bar;
		static::$nonexistent;
	}

}
