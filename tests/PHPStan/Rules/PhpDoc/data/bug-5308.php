<?php declare(strict_types = 1);

namespace Bug5308;

class HelloWorld
{
	/**
	 * @param string $s
	 */
	public function sayHello($s): void
	{
		/**
		 * @var string $s
		 */
		$s = "ab";
		
		/**
		 * @var string
		 */
		$s = "ab";
	}
}
