<?php declare(strict_types = 1);

namespace Bug13930;

class HelloWorld
{
	public function sayHello() : void
	{
		var_dump(chr(256));
	}
}
