<?php

namespace ResultCacheE2EScannedExportedNodes;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
