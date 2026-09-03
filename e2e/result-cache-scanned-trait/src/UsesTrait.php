<?php

namespace ResultCacheE2EScannedTrait;

class UsesTrait
{

	use DepTrait;

	public function doIt(): int
	{
		return $this->doDep();
	}

}
