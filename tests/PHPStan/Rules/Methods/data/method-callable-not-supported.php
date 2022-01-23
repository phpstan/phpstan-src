<?php // lint >= 8.1

namespace MethodCallableNotSupported;

class Foo
{

	public function doFoo(): void
	{
		$this->doFoo(...);
	}

}
