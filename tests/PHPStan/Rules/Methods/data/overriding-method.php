<?php

namespace OverridingFinalMethod;

class Foo
{

	final public function doFoo()
	{

	}

	public function doBar()
	{

	}

	public function doBaz()
	{

	}

	protected function doLorem()
	{

	}

	public static function doIpsum()
	{

	}

	public function doDolor()
	{

	}

}

class Bar extends Foo
{

	public function doFoo()
	{

	}

	private function doBar()
	{

	}

	protected function doBaz()
	{

	}

	private function doLorem()
	{

	}

	public function doIpsum()
	{

	}

	public static function doDolor()
	{

	}

}

class Baz
{

	public function __construct(int $i)
	{

	}

}

class Lorem extends Baz
{

	public function __construct(string $s)
	{

	}

}
