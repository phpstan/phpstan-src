<?php // lint >= 8.2

namespace ReadonlyClassPropertyAssign;

readonly class Foo
{

	private int $foo;

	protected int $bar;

	public int $baz;

	public function __construct(int $foo)
	{
		$this->foo = $foo; // constructor - fine
	}

	public function setFoo(int $foo): void
	{
		$this->foo = $foo; // setter - report
	}

}
