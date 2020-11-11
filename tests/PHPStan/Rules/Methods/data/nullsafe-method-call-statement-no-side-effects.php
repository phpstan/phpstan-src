<?php // lint >= 8.0

namespace NullsafeMethodCallNoSideEffects;

class Foo
{

	public function doFoo(?\Exception $e): void
	{
		$e?->getMessage();
	}

}
