<?php

namespace InitializerExprTypeResolver;

use function PHPStan\Testing\assertType;

class Foo
{

	public const COALESCE_SPECIAL = [][0] ?? 42;
	public const COALESCE = [0, 1, 2, null][self::UNKNOWN] ?? "foo";
	public const TERNARY_SHORT = ["foo", true, false][self::UNKNOWN] ?: "bar";
	public const TERNARY_FULL = ["foo", true, false][self::UNKNOWN] ? "foo" : "bar";

	public function doFoo(): void
	{
		assertType('*ERROR*', self::COALESCE_SPECIAL); // could be 42
		assertType("0|1|2|'foo'", self::COALESCE);
		assertType("'bar'|'foo'|true", self::TERNARY_SHORT);
		assertType("'bar'|'foo'", self::TERNARY_FULL);
	}

}
