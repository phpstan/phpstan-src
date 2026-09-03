<?php declare(strict_types = 1);

namespace ResultCacheE2EImportType;

class User extends Base
{

	public function doUser(): int
	{
		return $this->doBase();
	}

}
