<?php

namespace Bug8346;

class HelloWorld
{
	public function speak(): string
	{
		return $this->sayhello('world');
	}

	private function sayHello(string $name): string
	{
		return 'Hello ' . $name;
	}
}
