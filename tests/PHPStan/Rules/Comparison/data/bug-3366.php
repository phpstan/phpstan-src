<?php

namespace Bug3366;

class Foo
{

	function sayHello(string $name): void
	{
		if ($name === '' ||
			$name === '1' ||
			$name === '2' ||
			$name === '3' ||
			$name === '4' ||
			$name === '5' ||
			$name === '6' ||
			$name === '7' ||
			$name === '8' ||
			$name === '9' ||
			$name === '0'
		) {
			echo 'hello';
		}
	}

}
