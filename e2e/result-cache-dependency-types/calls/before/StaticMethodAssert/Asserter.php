<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\StaticMethodAssert;

class Asserter
{

	/**
	 * @phpstan-assert Foo $value
	 */
	public static function isFoo(mixed $value): void
	{
	}

}
