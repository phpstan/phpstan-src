<?php declare(strict_types = 1);

namespace Bug11732Php8;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		assertType('false', 0 === '');
		assertType('false', 0 == '');
		assertType('false', 0 <= '');
	}
}
