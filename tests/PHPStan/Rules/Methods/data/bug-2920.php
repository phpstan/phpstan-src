<?php

namespace Bug2920MethodCall;


class HelloWorld
{
	public function sayHello(): void
	{
		$a = 'a';
		$b = 'b';

		$this->$a();
		$this->$b(1);
		$this->$b("1");

		$this->b(1);
		$this->b("1");
	}

	public function b(string $s): void
	{
	}

}

