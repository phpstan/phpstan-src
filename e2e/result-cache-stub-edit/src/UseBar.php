<?php

namespace ResultCacheE2EStubEdit;

class UseBar
{

	public function run(Bar $bar): string
	{
		return $bar->get();
	}

}
