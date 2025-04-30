<?php

namespace IgnoreNoComments;

class Foo
{
	public function doFoo(): void
	{
		echo $foo; // @phpstan-ignore variable.undefined
		echo $foo; // @phpstan-ignore wrong.id (comment)

		echo $foo, $bar;  // @phpstan-ignore variable.undefined, wrong.id
		echo $foo, $bar;  // @phpstan-ignore variable.undefined (comment), wrong.id
		echo $foo, $bar;  // @phpstan-ignore variable.undefined, wrong.id (comment)
		echo $foo, $bar;  // @phpstan-ignore variable.undefined (comment), wrong.id (comment)
	}

}
