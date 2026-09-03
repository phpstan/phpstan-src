<?php

namespace ResultCacheE2EScannedNewFile;

class UsesNew
{

	public function doUsesNew(): int
	{
		return (new NewlyScanned())->doNew();
	}

}
