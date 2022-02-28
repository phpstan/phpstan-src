<?php

namespace Bug6212;

class HelloWorld
{
	public function sayHello(\DateTimeImmutable $date): void
	{
		for ($i = 1; $i <= 1e18; $i *= 10) {

		}
	}
}
