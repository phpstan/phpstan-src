<?php // lint >= 8.6

namespace MethodPartialFunctionApplication;

class Foo
{

	public function doFoo(int $a, int $b): void
	{
	}

	public static function doBar(int $a, int $b): void
	{
	}

	public function test(): void
	{
		$this->doFoo(...);
		$this->doFoo(1, ?);
		self::doBar(...);
		self::doBar(?, 2);
	}

}
