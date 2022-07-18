<?php // lint >= 8.1

namespace ReadOnlyPropertyAssignRefPhpDocAndNative;

class Foo
{

	/** @readonly */
	private readonly int $foo;

	/** @readonly */
	public readonly int $bar;

	public function doFoo()
	{
		$foo = &$this->foo;
		$bar = &$this->bar;
	}

}
