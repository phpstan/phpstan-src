<?php // lint >= 8.0

namespace NativeUnionTypesSupport;

class Foo
{

	public function doFoo(int|bool $foo): int|bool
	{
		return 1;
	}

	public function doBar(): int|bool
	{

	}

}
