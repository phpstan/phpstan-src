<?php declare(strict_types=1);

namespace LooseCompareUnion;

class HelloWorld
{
	public function sayHello(string $s): void
	{
		var_dump(preg_match('{[A-Z]}', $s) == 2);
	}
}
