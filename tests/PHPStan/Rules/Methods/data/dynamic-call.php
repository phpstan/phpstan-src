<?php

namespace MethodsDynamicCall;

final class Foo
{

	/** @var 'foo'|'bar'|'buz'|'qux' */
	public static $name;

	public function foo(): void
	{
	}

	public static function qux(): void
	{
	}

	public function test(string $string, object $obj): void
	{
		$foo = 'bar';

		echo $this->$foo();
		echo $this->$string();
		echo $this->$obj();
		echo $this->{self::$name}();
	}

	public function testStaticCall(string $string, object $obj): void
	{
		$foo = 'bar';

		echo self::$foo();
		echo self::$string();
		echo self::$obj();
		echo self::{self::$name}();
	}
}
