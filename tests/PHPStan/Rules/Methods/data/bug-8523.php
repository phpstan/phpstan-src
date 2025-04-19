<?php

namespace Bug8523;

class HelloWorld
{
	public static ?HelloWorld $instance = null;

	public static function bazz(): void
	{
		self::$instance = null;
	}

	public function foo(): void
	{
		self::$instance = new HelloWorld();

		self::bazz();

		self::$instance?->foo();
	}

	public function bar(): void
	{
		self::$instance = null;
	}

	public function baz(): void
	{
		self::$instance = new HelloWorld();

		$this->bar();

		self::$instance?->foo();
	}
}
