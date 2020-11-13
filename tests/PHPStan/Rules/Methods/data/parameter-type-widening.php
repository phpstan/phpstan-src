<?php

namespace ParameterTypeWidening;

class Foo
{

	public function doFoo(string $foo): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo($foo): void
	{

	}

}
