<?php declare(strict_types = 1);

namespace Bug10202;

class HelloWorld
{
	public function sayHello(): void
	{
		$x = true;

		if (rand(0,1)) {
			$x = true;
		} else {
			$x = false;
		}
	}
}
