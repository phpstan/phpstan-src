<?php

namespace Bug5881;

class HelloWorld
{
	public function sayHello(mixed $stuff): void
	{
		var_dump(array_values((array) $stuff));
	}
}
