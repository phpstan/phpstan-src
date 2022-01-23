<?php // lint >= 8.1

namespace FirstClassMethodCallable;

class Foo
{

	public function doFoo(int $i): void
	{
		$this->doFoo(...);
	}

}
