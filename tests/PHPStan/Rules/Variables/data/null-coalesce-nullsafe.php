<?php // lint >= 8.0

namespace NullCoalesceNullsafe;

class Foo
{

	public function doFoo(
		$mixed,
		\Exception $nonNullable,
		?\Exception $nullable
	)
	{
		$mixed?->foo;
		$nonNullable?->foo;
		$nullable?->foo;
	}

}
