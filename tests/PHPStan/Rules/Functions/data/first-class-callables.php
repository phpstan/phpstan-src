<?php // lint >= 8.1

namespace FirstClassFunctionCallable;

class Foo
{

	public function doFoo(): void
	{
		$f = json_encode(...);
	}

}
