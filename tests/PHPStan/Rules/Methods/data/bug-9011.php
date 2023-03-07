<?php

declare(strict_types=1);

namespace Bug9011;

class HelloWorld
{
	/**
	 * @return string[]
	 */
	public function getX(): array
	{
		$a = array('green', 'red', 'yellow', 'y');
		$b = array('avocado', 'apple', 'banana');
		return array_combine($a, $b);
	}
}
