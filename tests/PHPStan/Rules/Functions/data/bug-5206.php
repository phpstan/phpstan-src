<?php declare(strict_types = 1);

namespace Bug5206;

class HelloWorld
{
	public function sayHello(): \Closure
	{
		return fn (mixed $mixed) => '`mixed` type!';
	}
}
