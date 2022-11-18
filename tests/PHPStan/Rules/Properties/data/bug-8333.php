<?php declare(strict_types = 1);

namespace Bug8333;

class Foo {
	/** @var Bar|null */
	private static $root;

	static public function setRoot(?Bar $bar)
	{
		static::$root = $bar;
	}

	static public function checkRoot(): bool {
		if (static::$root === null) {
			return false;
		}
		return static::$root::$root !== null;
	}
}

class Bar extends Foo {

}

class FooSelf
{
	/** @var BarSelf|null */
	private static $root;

	static public function setRoot(?FooSelf $bar)
	{
		self::$root = $bar;
	}

	static public function checkRoot(): bool
	{
		if (self::$root === null) {
			return false;
		}
		return self::$root::$root !== null;
	}
}

class BarSelf extends FooSelf
{

}

class FooAccessProperties
{

	static private $foo;

	static protected $bar;

	static public $ipsum;

}

class BarAccessProperties extends FooAccessProperties
{

	static private $foobar;

	public function foo()
	{
		static::$loremipsum; // nonexistent
		static::$foo; // private from an ancestor
		static::$bar;
		static::$ipsum;
		static::$foobar;
	}

}
