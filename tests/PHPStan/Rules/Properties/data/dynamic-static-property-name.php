<?php declare(strict_types = 1);

namespace DynamicStaticPropertyName;

use Stringable;

class Foo
{

	public static string $bar = '';

	/**
	 * @param string|int $stringOrInt
	 * @param string|object $stringOrObject
	 * @param int|object $intOrObject
	 */
	public function test(string $name, Stringable $stringable, int $int, array $array, object $object, ?string $nullableString, $stringOrInt, $stringOrObject, $intOrObject): void
	{
		echo self::${$object}; // error - object is not stringable
		echo self::${$array}; // error - array is not a string

		echo self::${$name}; // valid
		echo self::${$stringable}; // valid - Stringable is allowed
		echo self::${$int}; // valid - int is castable to string

		self::${$object} = 'x'; // error - object is not stringable (reported once)
		self::${$name} = 'x'; // valid

		echo self::${$nullableString}; // valid - null is castable to string
		echo self::${$stringOrInt}; // valid - both castable to string
		echo self::${$stringOrObject}; // error - object part is not stringable
		echo self::${$intOrObject}; // error - object part is not stringable
	}

}
