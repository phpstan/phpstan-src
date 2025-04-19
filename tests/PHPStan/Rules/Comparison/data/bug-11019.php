<?php

namespace Bug11019;

class HelloWorld
{
	public static int $a;

	public function reset(): void
	{
		static::$a = rand(1,10);
	}

	public function sayHello(): void
	{
		$this->reset();
		assert(static::$a === 1);
		$this->reset();
		assert(static::$a === 1);
	}
}
