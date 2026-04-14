<?php

namespace Bug5865While;

final class HelloWorld
{

	public function alwaysThrows(): void
	{
		throw new \Exception();
	}

	public function whileLiteralTrue(): void
	{
		while (true) {
			$this->alwaysThrows();
		}
	}

	public static function staticAlwaysThrows(): void
	{
		throw new \Exception();
	}

	public function whileStaticCall(): void
	{
		while (true) {
			self::staticAlwaysThrows();
		}
	}

}

function alwaysThrowsFunction(): void
{
	throw new \Exception();
}

function whileFunctionCall(): void
{
	while (true) {
		alwaysThrowsFunction();
	}
}
