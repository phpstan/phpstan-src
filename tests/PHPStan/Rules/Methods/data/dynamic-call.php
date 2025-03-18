<?php

namespace MethodsDynamicCall;

final class Foo
{

	/** @var 'doFoo'|'doBar'|'doBuz'|'doQux' */
	public static $name;

	public function doFoo(int $n = 0): void
	{
	}

	public static function doQux(string $s = 'default'): void
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

	public function testScope(): void
	{
		$param1 = 1;
		$param2 = 'str';
		$name1 = 'doFoo';
		if (rand(0, 1)) {
			$name = $name1;
			$param = $param1;
		} else {
			$name = 'doQux';
			$param = $param2;
		}

		$this->$name($param); // ok
		$this->$name1($param);
		$this->$name($param1);
		$this->$name($param2);

		self::$name($param); // ok
		self::$name1($param);
		self::$name($param1);
		self::$name($param2);
	}
}
