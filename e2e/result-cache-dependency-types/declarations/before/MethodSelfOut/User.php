<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodSelfOut;

class User
{

	/**
	 * @phpstan-self-out self&Foo
	 */
	public function user(): void
	{
	}

}
