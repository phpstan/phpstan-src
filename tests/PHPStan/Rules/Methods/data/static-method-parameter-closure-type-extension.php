<?php declare(strict_types = 1);

namespace StaticMethodParameterClosureTypeExtension;

class Foo
{
	/** @param callable(int): int $fn */
	public static function foo(callable $fn): void
	{
	}

	public function bar(): void
	{
		self::foo($this->callback1(...));
		self::foo($this->callback2(...));
		self::foo($this->callback3(...));
	}

	private function callback1(int $a): int
	{
		return $a;
	}

	/** @param 5|7 $a */
	private function callback2(int $a): int
	{
		return $a;
	}

	/** @param 5 $a */
	private function callback3(int $a): int
	{
		return $a;
	}
}
