<?php // lint >= 8.1

namespace GenericEnumParam;

enum FooEnum
{

}

class Foo
{

	/**
	 * @param FooEnum<int> $e
	 */
	public function doFoo(FooEnum $e): void
	{

	}

}
