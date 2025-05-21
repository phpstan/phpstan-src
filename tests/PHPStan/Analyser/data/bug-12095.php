<?php declare(strict_types = 1);

namespace Bug12095;

class HelloWorld
{
	public const XXX = '$';

	public function sayHello(): void
	{
		if(defined(self::XXX)) {
			die(0);
		}
	}
}
