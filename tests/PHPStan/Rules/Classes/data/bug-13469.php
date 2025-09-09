<?php

namespace Bug13469;

use Stringable;

trait FooTrait
{
	public function __toString(): string
	{
		return 'foo';
	}
}

class Foo
{
	use FooTrait;
}

function doFoo() {
	$foo = new Foo();

	var_dump($foo instanceof Stringable);
}

