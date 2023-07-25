<?php

namespace IgnoreParseError;

class Foo
{

	public function doFoo(): void
	{
		echo $foo; // @phpstan-ignore return.ref,, return.non

		/**
		 * @phpstan-ignore return.ref )return.non
		 */
		echo $foo;

		/*
		 * @phpstan-ignore return.ref (return.non
		 */
		echo $foo;
	}

}
