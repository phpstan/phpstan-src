<?php declare(strict_types = 1);

namespace DynamicStaticPropertyName;

use Stringable;

class Foo
{

	public static string $bar = '';

	public function test(string $name, Stringable $stringable, int $int, array $array, object $object): void
	{
		echo self::${$object}; // error - object is not stringable
		echo self::${$array}; // error - array is not a string

		echo self::${$name}; // valid
		echo self::${$stringable}; // valid - Stringable is allowed
		echo self::${$int}; // valid - int is castable to string

		self::${$object} = 'x'; // error - object is not stringable (reported once)
		self::${$name} = 'x'; // valid
	}

}
