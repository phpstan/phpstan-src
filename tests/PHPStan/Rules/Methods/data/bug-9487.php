<?php declare(strict_types = 1);

namespace Bug9487;

class HelloWorld
{
	/** @param list<string> $x */
	public function sayHello($x): void
	{
	}

	/** @param array<positive-int, string> $x */
	public function invoke($x): void
	{
		$this->sayHello($x);
	}
}
