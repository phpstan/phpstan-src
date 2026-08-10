<?php declare(strict_types = 1);

namespace PharBootstrapResultCache;

class HelloWorld
{

	public function sayHello(string $name): string
	{
		return sprintf('Hello, %s', $name);
	}

}
