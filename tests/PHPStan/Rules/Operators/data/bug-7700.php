<?php declare(strict_types = 1);

class HelloWorld
{
	/** @param int|float $val */
	public function sayHello($val): void
	{
		1.0 >> 22;
		22 << 1.0;

		1.0 & 22;
		22 & 1.0;

		1.0 | 22;
		22 | 1.0;

		1.0 ^ 22;
		22 ^ 1.0;

		$val >> 1;
	}
}
