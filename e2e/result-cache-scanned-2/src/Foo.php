<?php

namespace ResultCacheE2EScanned2;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
