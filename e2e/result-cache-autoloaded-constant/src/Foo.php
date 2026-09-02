<?php

namespace ResultCacheE2EAutoloadedConstant;

class Foo
{

	public function doFoo(): void
	{
		\PHPStan\dumpType(Dep::CON);
	}

}
