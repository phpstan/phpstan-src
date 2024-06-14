<?php

namespace Bug8635;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function EchoInt(int $value): void
	{
		assertType('numeric-string', "$value");
	}
}
