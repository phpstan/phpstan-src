<?php

namespace Bug10600;

class HelloWorld
{
	/** @param array{1, 2} $array */
	public static function sayHello(array $array): void
	{
		array_values($array)[0];
	}
}

$a = [0 => 1, 1 => 2];
$b = [1 => 2, 0 => 1];

HelloWorld::sayHello($a);
HelloWorld::sayHello($b);
