<?php

namespace VariadicParameterAlwaysOptional;

class Foo
{

	public function doFoo(string ...$test): void
	{

	}

	public function doBar(): void
	{

	}

}

class Bar extends Foo
{

	public function doFoo(string ...$test): void
	{

	}

	public function doBar(...$test): void
	{

	}

}
