<?php declare(strict_types = 1);

namespace Bug8568;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		assertType('non-falsy-string', 'a' . $this->get());
	}

	public function get(): ?int
	{
		return rand() ? 5 : null;
	}
}
