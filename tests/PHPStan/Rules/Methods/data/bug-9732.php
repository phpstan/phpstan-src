<?php declare(strict_types = 1);

namespace Bug9732;

class HelloWorld
{
	/**
	 * @phpstan-template TKeyType of string
	 * @phpstan-template TValueType
	 * @phpstan-param array<TKeyType, TValueType> $array
	 * @phpstan-return \Generator<TKeyType, TValueType, void, void>
	 */
	public static function stringifyKeys(array $array) : \Generator{
		foreach($array as $key => $value){
			yield (string) $key => $value;
		}
	}

	public function sayHello(): void
	{
		self::stringifyKeys($GLOBALS);
	}
}
