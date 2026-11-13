<?php declare(strict_types = 1);

namespace ResultCacheE2EDependencyTypes\MethodSelfOut;

class Builder
{

	/**
	 * @phpstan-self-out self&Foo
	 */
	public function mark(): void
	{
	}

}
