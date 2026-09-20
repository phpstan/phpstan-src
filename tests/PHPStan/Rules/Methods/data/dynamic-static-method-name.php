<?php declare(strict_types = 1);

namespace DynamicStaticMethodName;

use Stringable;

class Foo
{

	public static function doFoo(): void
	{
	}

	public static function test(string $name, Stringable $stringable, int $int, object $object): void
	{
		self::$object(); // error - object is not a string
		self::$stringable(); // error - method names cannot be Stringable
		self::$int(); // error - int is not a string

		self::$name(); // valid
	}

}
