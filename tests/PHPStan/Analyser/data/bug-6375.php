<?php

namespace Bug6375;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param int<-2147483648, 2147483647> $i
	 * @return void
	 */
	public function sayHello($i): void
	{
		$a = [];
		$a[$i] = 5;
		assertType('non-empty-array<int<-2147483648, 2147483647>, 5>', $a);
	}
}
