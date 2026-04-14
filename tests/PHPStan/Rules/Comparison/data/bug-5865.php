<?php

namespace Bug5865;

final class HelloWorld
{

	public function alwaysThrows(): void
	{
		throw new \Exception();
	}

	public function doWhileLiteralTrue(): void
	{
		do {
			$this->alwaysThrows();
		} while (true); // should not report - body always terminates
	}

	public function doWhileVariableCondition(): void
	{
		$a = true;
		do {
			$this->alwaysThrows();
			$a = false;
		} while ($a); // should not report - body always terminates
	}

	public function neverReturnType(): never
	{
		throw new \Exception();
	}

	public function doWhileNeverReturnType(): void
	{
		do {
			$this->neverReturnType();
		} while (true); // already works - no error
	}

	public static function staticAlwaysThrows(): void
	{
		throw new \Exception();
	}

	public function doWhileStaticCall(): void
	{
		do {
			self::staticAlwaysThrows();
		} while (true); // should not report - body always terminates
	}

}

function alwaysThrowsFunction(): void
{
	throw new \Exception();
}

function doWhileFunctionCall(): void
{
	do {
		alwaysThrowsFunction();
	} while (true); // should not report - body always terminates
}
