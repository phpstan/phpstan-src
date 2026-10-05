<?php declare(strict_types = 1);

namespace Bug6319;

class A {}

class HelloWorld
{
	public static function isLower(): bool
	{
		return true;
	}
	
	public function sayHello(): string
	{
		return \Closure::bind(self::isLower() ? fn () => 'LC' : fn () => 'not-LC', null, A::class)();
	}
}

var_dump(
    (new HelloWorld())->sayHello()
);
