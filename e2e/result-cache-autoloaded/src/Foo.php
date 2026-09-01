<?php

namespace ResultCacheE2EAutoloaded;

use ResultCacheE2EAutoloadedOther\Dep;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
