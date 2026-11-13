<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ParameterByRef;

class Foo
{

	public function take(int &$value): void
	{
	}

}
