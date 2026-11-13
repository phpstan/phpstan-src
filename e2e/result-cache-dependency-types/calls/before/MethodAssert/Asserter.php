<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodAssert;

class Asserter
{

	/**
	 * @phpstan-assert Foo $value
	 */
	public function isFoo(mixed $value): void
	{
	}

}
