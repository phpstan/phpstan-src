<?php // lint >= 8.1

namespace MethodCallable;

class Foo
{

	public function doFoo(int $i): void
	{
		$this->doFoo(...);
		$this->dofoo(...);
		$this->doNonexistent(...);
		$i->doFoo(...);
	}

	public function doBar(Bar $bar): void
	{
		$bar->doBar(...);
	}

	public function doBaz(Nonexistent $n): void
	{
		$n->doFoo(...);
	}

}

class Bar
{

	private function doBar()
	{

	}

}

class ParentClass
{

	private function doFoo()
	{

	}

}

class ChildClass extends ParentClass
{

	public function doBar()
	{
		$this->doFoo(...);
	}

}

/**
 * @method void doBar()
 */
class Lorem
{

	public function doFoo()
	{
		$this->doBar(...);
	}

	public function __call($name, $arguments)
	{

	}


}

/**
 * @method void doBar()
 */
class Ipsum
{

	public function doFoo()
	{
		$this->doBar(...);
	}

}
