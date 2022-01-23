<?php

namespace InstantiationCallable;

class Foo
{

	public function doFoo()
	{
		$a = new self();
		$f = new self(...);
	}

}
