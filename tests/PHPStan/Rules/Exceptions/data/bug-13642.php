<?php declare(strict_types = 1);

namespace Bug13642;

class HelloWorld
{
	/** @throws void */
	public function sayHello(): void
	{
		array_combine([1, 2], [1, 2]);
	}

	/**
	 * @param mixed $mixed1
	 * @param mixed $mixed2
	 *
	 * @throws void
	 */
	public function sayHello2($mixed1, $mixed2): void
	{
		array_combine($mixed1, $mixed2);
	}
}
