<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\MethodAttribute;

class Foo
{

	#[\Deprecated]
	public function value(): int
	{
		return 1;
	}

}
