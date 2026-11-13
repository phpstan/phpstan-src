<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodParamOut;

interface User
{

	/**
	 * @param-out Foo|null $foo
	 */
	public function user(mixed &$foo): void;

}
