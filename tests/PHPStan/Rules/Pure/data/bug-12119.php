<?php

namespace Bug12119;

/** @phpstan-pure */
function testFunction(string $s): string { return ''; }

class HelloWorld
{
	/** @phpstan-pure */
	public function testMethod(string $s): string { return ''; }

	/** @phpstan-pure */
	public function sayHello(string $s): string
	{
		$a = $this->testMethod('random_int');
		$b = testFunction('random_int');

		return $a . $b;
	}
}
