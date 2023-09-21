<?php

namespace IgnoreIdentifiers;

class Foo
{

	public function doFoo(): void
	{
		echo $foo; // @phpstan-ignore variable.undefined

		echo $foo; // @phpstan-ignore wrong.id

		echo $foo, $bar;

		echo $foo, $bar;  // @phpstan-ignore variable.undefined

		echo $foo, $bar;  // @phpstan-ignore variable.undefined, variable.undefined
	}

}
