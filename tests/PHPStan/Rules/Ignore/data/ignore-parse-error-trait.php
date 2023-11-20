<?php

namespace IgnoreParseErrorTrait;

trait Foo
{

	public function doFoo(): void
	{
		echo $foo; // @phpstan-ignore return.ref,, return.non
	}

}
