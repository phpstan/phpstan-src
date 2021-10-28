<?php

namespace Bug1971;

class HelloWorld
{
	public static function sayHello(): void
	{
		echo 'Hello';
	}

	public function getClosure(): void
	{
		$closure1 = \Closure::fromCallable([self::class, 'sayHello']);
		$closure2 = \Closure::fromCallable([static::class, 'sayHello']);
		$closure2 = \Closure::fromCallable([static::class, 'sayHello2']);
	}
}
