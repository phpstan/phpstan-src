<?php

namespace ResultCacheE2EScannedNewFile;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
