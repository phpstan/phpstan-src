<?php declare(strict_types = 1); // lint >= 8.0

namespace Pr4385Bis;

class HelloWorld
{
	/** @param array<string, int> $a */
	public function sayHello(array $a, mixed $m): void
	{
		echo $a[$m];
	}
}
