<?php

namespace Bug10036;

use stdClass;

class HelloWorld
{
	public function sayHello(?string $class): void
	{
		var_dump(new stdClass instanceof $class);
	}

	/**
	 * @param string|null $class
	 */
	public function sayWorld($class): void
	{
		var_dump(new stdClass instanceof $class);
	}

	public function sayString(string $class): void
	{
		var_dump(new stdClass instanceof $class);
	}

	public function sayMixed(mixed $class): void
	{
		var_dump(new stdClass instanceof $class);
	}

	public function sayObject(object $class): void
	{
		var_dump(new stdClass instanceof $class);
	}

	public function sayArray(array $class): void
	{
		var_dump(new stdClass instanceof $class);
	}
}
