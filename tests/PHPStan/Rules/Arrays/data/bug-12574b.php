<?php declare(strict_types = 1);

namespace Bug12574b;

class HelloWorld
{
	public function sayHello2(string $a): void
	{
		$b = [];

		$b[$a] = 'abc';

		echo $b[$a];
	}
}
