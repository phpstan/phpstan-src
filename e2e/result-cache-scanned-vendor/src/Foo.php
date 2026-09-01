<?php

namespace ResultCacheE2EScannedVendor;

use ResultCacheE2EScannedVendorLib\Dep;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
