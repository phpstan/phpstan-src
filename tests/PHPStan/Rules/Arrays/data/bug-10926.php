<?php // lint >= 8.0

namespace Bug10926;

class HelloWorld
{
	public function sayHello(?\stdClass $date): void
	{
		$date ??= new \stdClass();
		echo isset($date['a']);
	}
}
