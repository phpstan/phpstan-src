<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodThrows;

interface User
{

	/**
	 * @throws Foo
	 */
	public function user(): void;

}
