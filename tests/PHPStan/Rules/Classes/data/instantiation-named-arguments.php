<?php

namespace InstantiationNamedArguments;

class Foo
{

	public function __construct(int $i, int $j)
	{

	}

	public function doFoo()
	{
		$s = new self(i: 1);
		$r = new self(i: 1, j: 2, z: 3);
	}

}
