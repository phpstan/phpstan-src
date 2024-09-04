<?php // lint >= 8.1

namespace ArrowFunctionNeverReturn;

class Baz
{

	public function doFoo(): void
	{
		$f = fn () => throw new \Exception();
		$g = fn (): never => throw new \Exception();
		$g = fn (): never => 1;
	}

}
