<?php declare(strict_types = 1);

namespace Bug11483;

class HelloWorld
{
	public function sayHello(): void
	{
		$hello = 'he';
		$hello = 'llo';
		echo $hello;
	}
}
