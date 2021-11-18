<?php

namespace Bug5782;

class HelloWorld
{
	public static function sayHello(): void
	{

	}

	public function sayGoodbye(): void
	{

	}

}

class FooWorld
{
	public function bazBar(): void
	{
		$closure1 = \Closure::fromCallable([\Bug5782\HelloWorld::class, 'sayGoodbye']);
	}
}

function baz() {
	$closure1 = \Closure::fromCallable([\Bug5782\HelloWorld::class, 'sayHello']);
	$closure2 = \Closure::fromCallable('\Bug5782\HelloWorld::sayHello');
	$closure3 = \Closure::fromCallable([\Bug5782\HelloWorld::class, 'sayGoodbye']);
	$closure4 = \Closure::fromCallable('Bug5782\HelloWorld::sayGoodbye');
}
