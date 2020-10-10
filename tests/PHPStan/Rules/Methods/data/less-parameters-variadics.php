<?php

namespace LessParametersVariadics;

class Foo
{

	public function doFoo(int $many, string $parameters, string $here)
	{

	}

}

class Bar extends Foo
{

	public function doFoo(...$everything)
	{

	}

}

class Baz extends Foo
{

	public function doFoo(int ...$everything)
	{

	}

}

class Lorem extends Foo
{

	public function doFoo(int $many, string ...$everything)
	{

	}

}
