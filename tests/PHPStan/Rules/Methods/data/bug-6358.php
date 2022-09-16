<?php declare(strict_types = 1);

namespace Bug6358;

use stdClass;

class HelloWorld
{
	/**
	 * @return list<stdClass>
	 */
	public function sayHello(): array
	{
		return [1 => new stdClass];
	}
}
