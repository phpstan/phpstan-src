<?php

namespace EarlyTermination;

class Foo
{
	public static function doBar()
	{
		throw new \Exception();
	}

	public function doFoo()
	{
		throw new \Exception();
	}

	/**
	 * @return no-return
	 */
	public static function doBarPhpDoc()
	{
		throw new \Exception();
	}

	/**
	 * @return never-return
	 */
	public function doFooPhpDoc()
	{
		throw new \Exception();
	}
}

class Bar extends Foo
{

}

function baz()
{
	throw new \Exception();
}

/**
 * @return never
 */
function bazPhpDoc()
{
	throw new \Exception();
}

/**
 * @return never-returns
 */
function bazPhpDoc2()
{
	throw new \Exception();
}
