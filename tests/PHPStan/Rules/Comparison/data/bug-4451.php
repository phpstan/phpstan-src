<?php

namespace Bug4451;

class HelloWorld
{
	public function sayHello(): int
	{
		$verified = fn(): bool => rand() === 1;

		return match([$verified(), $verified()]) {
			[true, true] => 1,
			[true, false] => 2,
			[false, true] => 3,
			[false, false] => 4,
		};

	}
}
