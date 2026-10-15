<?php

namespace ResultCacheE2EStubEdit;

class UseFoo
{

	public function run(Foo $foo): string
	{
		return $foo->get();
	}

}
