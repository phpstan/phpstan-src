<?php declare(strict_types = 1);

namespace Bug4732;

class HelloWorld
{
	const FOO_BAR = 1;
	const FOO_BAZ = 2;

	/**
	 * @param int-mask-of<self::FOO_*> $flags bitflags options
	 */
	public static function sayHello(int $flags): void
	{
	}

	public static function test(): void
	{
		HelloWorld::sayHello(HelloWorld::FOO_BAR | HelloWorld::FOO_BAZ);
		HelloWorld::sayHello(HelloWorld::FOO_BAR);
		HelloWorld::sayHello(HelloWorld::FOO_BAZ);
	}
}
