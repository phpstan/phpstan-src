<?php declare(strict_types = 1);

namespace Bug9428;

class HelloWorld
{
	public function sayHello(): void
	{
		var_dump(base64_decode(string: "dGVzdA=="), true);
	}
}
