<?php

namespace Bug5164;

class HelloWorld
{
	public function sayHello(): void
	{
		$isInstanceOf = static function (string $class) {
			return static function ($item) use ($class): bool {
				return $item instanceof $class;
			};
		};
	}
}
