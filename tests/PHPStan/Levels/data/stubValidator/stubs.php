<?php

namespace StubValidator;

use Countable;

class Foo implements Countable
{

	public function count(): int
	{
		return 1;
	}

	public function doFoo(array $argument)
	{

	}

}

function someFunction(array $argument)
{

}

new class () extends \ArrayIterator
{

	public function doFoo(Foooooooo $foo)
	{

	}

};
