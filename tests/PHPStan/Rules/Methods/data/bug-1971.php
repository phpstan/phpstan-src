<?php

namespace Bug1971;

class HelloWorld
{
	public function sayHello(): void
	{
		echo 'Hello';
	}

	public function getClosure(): void
	{
		$closure1 = \Closure::fromCallable([self::class, 'sayHello']);
		$closure2 = \Closure::fromCallable([static::class, 'sayHello']);
		$closure3 = \Closure::fromCallable('Bug1971\HelloWorld::sayHello');
		$closure4 = \Closure::fromCallable([\Bug1971\HelloWorld::class, 'sayHello']);
		$closure2 = \Closure::fromCallable([static::class, 'sayHello2']);
	}
}
