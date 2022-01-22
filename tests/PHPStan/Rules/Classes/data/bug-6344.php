<?php

declare(strict_types=1);

namespace Bug6344;

use Generator;

class Foo {}

class Bar {}

class Bug6344
{
	public Foo|Bar $property;

	public function generate(): Generator
	{
		$this->property = new Foo;

		yield;

		if($this->property instanceof Foo) return;
	}
}
