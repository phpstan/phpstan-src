<?php

namespace Bug5354;

class HelloWorld
{
	/**
	 * @param mixed[] $foo
	 */
	public function sayHello(array $foo): void
	{
		$a = [];
		foreach ($foo as $e) {
			$a[] = rand(5, 15) > 10 ? 0 : 1;
		}

		if (\in_array(0, $a, true)) {
			return;
		}
	}
}
