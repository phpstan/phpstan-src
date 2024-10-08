<?php

namespace Bug2920bStaticCall;


class HelloWorld
{
	public function sayHello(): void
	{
		$a = 'a';
		$b = 'b';

		self::$a();
		self::$b(1);
		self::$b("1");

		self::b(1);
		self::b("1");
	}

	static public function b(string $s): void
	{
	}

}

