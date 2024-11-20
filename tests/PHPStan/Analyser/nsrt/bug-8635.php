<?php

namespace Bug8635;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function EchoInt(int $value): void
	{
		assertType('lowercase-string&numeric-string&uppercase-string', "$value");
	}
}
