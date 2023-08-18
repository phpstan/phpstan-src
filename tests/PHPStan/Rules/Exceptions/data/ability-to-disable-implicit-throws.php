<?php declare(strict_types = 1);

namespace AbilityToDisableImplicitThrows;

class HelloWorld
{
	public function sayHello(callable $c): void
	{
		try {
			$c();
		} catch (\Throwable $e) { // no error here

		}

		try {
			$this->method();
		} catch (\Throwable $e) { // Dead catch - Throwable is never thrown in the try block.

		}
	}

	public function method(): void
	{
	}
}
