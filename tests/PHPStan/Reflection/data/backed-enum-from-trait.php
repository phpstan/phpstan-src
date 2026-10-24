<?php declare(strict_types = 1); // lint >= 8.1

namespace BackedEnumFromTrait;

trait FooTrait
{

	public static function from(string $case): self
	{
		return self::ONE;
	}

	public static function tryFrom(string $case): ?static
	{
		return null;
	}

}

enum Foo: int
{

	use FooTrait;

	case ONE = 1;

}
