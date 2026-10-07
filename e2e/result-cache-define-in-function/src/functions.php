<?php

namespace ResultCacheE2EDefineInFunction;

function setUp(): int
{
	define('RESULT_CACHE_E2E_IN_FUNCTION', 1);

	return 1;
}

class Bootstrap
{

	public function boot(): void
	{
		define('RESULT_CACHE_E2E_IN_METHOD', 1);
	}

}
