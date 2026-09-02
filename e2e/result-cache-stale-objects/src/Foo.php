<?php declare(strict_types = 1);

namespace ResultCacheE2EStaleObjects;

class Foo
{

	public function doFoo(): int
	{
		return 'not an int';
	}

}
