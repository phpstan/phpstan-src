<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodReturnPhpDoc;

interface User
{

	/**
	 * @return Foo|null
	 */
	public function user(): ?object;

}
