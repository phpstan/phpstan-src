<?php declare(strict_types = 1); // lint >= 8.1

namespace MethodParameterClosureTypeExtension;

class Foo
{
	/** @param callable(int): int $fn */
	public function foo(callable $fn): void
	{
	}

	public function bar(): void
	{
		$this->foo($this->callback1(...));
		$this->foo($this->callback2(...));
		$this->foo($this->callback3(...));
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
