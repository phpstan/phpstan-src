<?php

namespace InitializerExprTypeResolver;

use function PHPStan\Testing\assertType;

class Foo
{

	public const COALESCE_SPECIAL = [][0] ?? 42;
	public const COALESCE = [0, 1, 2, null][self::UNKNOWN] ?? "foo";

	public function doFoo(): void
	{
		assertType('*ERROR*', self::COALESCE_SPECIAL); // could be 42
		assertType("0|1|2|'foo'", self::COALESCE);
	}

}
