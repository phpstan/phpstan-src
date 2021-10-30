<?php

namespace Bug4011;

class Foo extends \FilterIterator
{
	public function __construct(\Traversable $iterator)
	{

	}

	public function accept(): bool
	{
		return true;
	}
}

function (\Traversable $t) {
	new Foo($t);
};
