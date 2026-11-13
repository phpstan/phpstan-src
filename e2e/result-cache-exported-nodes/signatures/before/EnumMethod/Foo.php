<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumMethod;

enum Foo
{

	case A;

	public function value(): int
	{
		return 1;
	}

}
