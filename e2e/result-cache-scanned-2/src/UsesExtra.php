<?php

namespace ResultCacheE2EScanned2;

class UsesExtra
{

	public function doUsesExtra(Extra $extra): int
	{
		return $extra->doExtra();
	}

}
