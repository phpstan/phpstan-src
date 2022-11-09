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
