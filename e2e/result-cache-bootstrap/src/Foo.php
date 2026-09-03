<?php

namespace ResultCacheE2EBootstrap;

class Foo
{

	public function doFoo(Dep $dep): int
	{
		return $dep->doDep();
	}

}
