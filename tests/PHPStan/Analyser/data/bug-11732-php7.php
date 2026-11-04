<?php declare(strict_types = 1);

namespace Bug11732Php7;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		assertType('false', 0 === '');
		assertType('true', 0 == '');
		assertType('true', 0 <= '');
	}
}
