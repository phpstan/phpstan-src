<?php declare(strict_types = 1);

class HelloWorld
{
	public function sayHello(): void
	{
		$value = '123';

		declare(ticks=1) {
			echo $value;
		}
	}
}
