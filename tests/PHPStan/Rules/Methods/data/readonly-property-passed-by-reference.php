<?php // lint >= 8.1

namespace ReadonlyPropertyPassedByRef;

class Foo
{

	private int $foo;

	private readonly int $bar;

	public function doFoo()
	{
		$this->doBar($this->foo);
		$this->doBar($this->bar);
		$this->doBar(param: $this->bar);
	}

	public function doBar(&$param): void
	{

	}

}
