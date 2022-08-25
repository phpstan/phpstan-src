<?php declare(strict_types=1);

namespace Bug2851b;

class HelloWorld
{
	public function sayHello(iterable $input): void
	{
		$expected = [
			false,
			1,
			'x',
			'y',
		];

		foreach ($input as $_) {
			\assert(array_shift($expected) == $_);
		}

		\assert($expected === []);
		\assert(\count($expected) === 0);
	}
}
