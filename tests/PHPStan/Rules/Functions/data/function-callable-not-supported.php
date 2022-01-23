<?php // lint >= 8.1

namespace FunctionCallableNotSupported;

class Foo
{

	public function doFoo(): void
	{
		$f = json_encode(...);
	}

}
