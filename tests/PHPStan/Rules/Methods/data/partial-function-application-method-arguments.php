<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationMethodArguments;

class Foo
{

	public function doFoo(int $a, string $b, float ...$c): bool
	{
		return true;
	}

	public static function doStatic(int $a, string $b = 'x'): int
	{
		return 1;
	}

	public function test(): void
	{
		$this->doFoo(1, ?);
		$this->doFoo(1, ?, ?, ?);
		$this->doFoo(?, ...);
		self::doStatic(?);
		self::doStatic(1, ?, ...);

		$this->doFoo(?);
		$this->doFoo('x', ?);
		$this->doFoo(1, 'b', c: ?);
		self::doStatic(1, 'b', ?);
		self::doStatic(b: ?);
		static::doStatic(?, a: 1);
	}

}
