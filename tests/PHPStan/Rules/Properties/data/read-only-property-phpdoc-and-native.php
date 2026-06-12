<?php // lint >= 8.1

namespace ReadOnlyPropertyAndNative;

class Foo
{

	/** @readonly */
	private readonly int $foo;
	/** @readonly */
	private readonly $bar;
	/** @readonly */
	private readonly int $baz = 0;

}
