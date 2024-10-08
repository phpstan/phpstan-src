<?php

namespace Bug2920bStaticCall;


class HelloWorld
{
	public function sayHello(): void
	{
		$a = 'a';
		$b = 'b';

		$this::$a();
		$this::$b(1);
		$this::$b("1");

		$this::b(1);
		$this::b("1");
	}

	static public function b(string $s): void
	{
	}

}

