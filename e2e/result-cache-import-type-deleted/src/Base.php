<?php declare(strict_types = 1);

namespace ResultCacheE2EImportType;

/**
 * @phpstan-import-type MyAlias from Aliases
 */
class Base
{

	public function doBase(): int
	{
		return 1;
	}

}
