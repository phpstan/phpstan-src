<?php declare(strict_types=1);

namespace Bug12926;

class HelloWorld
{
	public function sayHello(array $arr): void
	{
		foreach (array_keys($arr) as $key) {
			var_dump($arr[$key]);
		}
	}
}
