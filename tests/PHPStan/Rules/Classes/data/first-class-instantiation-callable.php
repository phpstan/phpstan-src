<?php

namespace FirstClassInstantiationCallable;

class Foo
{

	public function doFoo(int $i): void
	{
		new self(...);
	}

}
