<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug9746;

class HelloWorld
{
	public function sayHello(?self $self): void
	{
		$self?->sayHello(...);
	}
}
