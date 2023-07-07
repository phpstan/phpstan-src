<?php

namespace ArrayKeys;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello($mixed): void
	{
		if(is_array($mixed)) {
			assertType('list<(int|string)>', array_keys($mixed));
		} else {
			assertType('mixed~array', $mixed);
			assertType('*NEVER*', array_keys($mixed));
		}
	}

	public function constantArrayType(): void
	{
		$numbers = array_filter(
			[1 => 'a', 2 => 'b', 3 => 'c'],
			static fn ($value) => mt_rand(0, 1) === 0,
		);
		assertType("array{0?: 1|2|3, 1?: 2|3, 2?: 3}", array_keys($numbers));
	}
}
