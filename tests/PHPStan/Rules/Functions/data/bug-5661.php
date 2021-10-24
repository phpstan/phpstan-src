<?php

namespace Bug5661;

class Foo
{

	/**
	 * @param array<string> $array
	 */
	function sayHello(array $array): void
	{
		echo join(', ', $array) . PHP_EOL;
	}

	/**
	 * @param string[] $array
	 */
	function sayHello2(array $array): void
	{
		echo join(', ', $array) . PHP_EOL;
	}

}
