<?php // lint >= 8.1

namespace StaticMethodCallable;

class Foo
{

	public static function doFoo()
	{
		self::doFoo(...);
		self::dofoo(...);
		Nonexistent::doFoo(...);
		self::nonexistent(...);
		self::doBar(...);
		Bar::doBar(...);
		Bar::doBaz(...);
	}

	public function doBar(Nonexistent $n, int $i)
	{
		$n::doFoo(...);
		$i::doFoo(...);
	}

}

abstract class Bar
{

	private static function doBar()
	{

	}

	abstract public static function doBaz();

}

/**
 * @method static void doBar()
 */
class Lorem
{

	public function doFoo()
	{
		self::doBar(...);
	}

	public function __call($name, $arguments)
	{

	}


}

/**
 * @method static void doBar()
 */
class Ipsum
{

	public function doFoo()
	{
		self::doBar(...);
	}

}
