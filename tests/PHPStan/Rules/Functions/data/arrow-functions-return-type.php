<?php // lint >= 7.4

namespace ArrowFunctionsReturnTypes;

class Foo
{

	public function doFoo(int $i)
	{
		fn() => $i;
		fn() => self::noop();
		fn(): int => $i;
		fn(): string => $i;
		fn(int $a): int => $a;
		fn(string $a): int => $a;
	}

	public static function noop(): void
	{
		return;
	}

}
