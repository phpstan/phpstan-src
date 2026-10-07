<?php declare(strict_types = 1);

namespace ResultCacheE2EFunctionSharesMethodName;

class Caller
{

	public function doCall(): int
	{
		return strlen(shared());
	}

}
