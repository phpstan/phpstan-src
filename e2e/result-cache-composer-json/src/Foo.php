<?php declare(strict_types = 1);

namespace ResultCacheE2EComposerJson;

class Foo
{

	public function doFoo(string $s): bool
	{
		return str_contains($s, 'a');
	}

}
