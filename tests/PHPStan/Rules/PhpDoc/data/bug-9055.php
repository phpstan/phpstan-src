<?php declare(strict_types = 1);

namespace Bug9055;

class HelloWorld
{
	public function sayHello(): void
	{
		if (!class_exists(a::class)) {
			return;
		}

		/** @var a $x */
		$x = getFromSomewhere();
		/** @var uncheckedNotExisting $x */
		$x = getFromSomewhere();
	}
}

function getFromSomewhere() {}
