<?php // lint >= 8.1

namespace ReadonlyPropertyAssignPhpDocAndNative;

class Foo
{

	/** @readonly */
	private readonly int $foo;

	/** @readonly */
	protected readonly int $bar;

	/** @readonly */
	public readonly int $baz;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
	}

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // setter - report
	}

}
