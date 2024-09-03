<?php // lint >= 8.1

namespace Bug11592;

trait HelloWorld
{
	abstract public static function cases(): array;

	abstract public static function from(): self;

	abstract public static function tryFrom(): ?self;
}

enum Test
{
	use HelloWorld;
}

enum Test2
{

	abstract public static function cases(): array;

	abstract public static function from(): self;

	abstract public static function tryFrom(): ?self;

}

enum BackedTest: int
{
	use HelloWorld;
}

enum BackedTest2: int
{
	abstract public static function cases(): array;

	abstract public static function from(): self;

	abstract public static function tryFrom(): ?self;
}

enum EnumWithAbstractMethod
{
	abstract function foo();
}
