<?php declare(strict_types = 1);

namespace PhpDoc\ParamTag;

class A {}
class B extends A {}
class C extends B {}

class GrandparentClass
{
	/** @param A $one */
	public function method($one, $two): void {} // error: $two has no type
}

class ParentClass extends GrandparentClass
{
	/** @param B $dos */
	public function method($uno, $dos): void {} // no error: type of $uno is inherited
}

class ChildClass extends ParentClass
{
	/** @param C $one */
	public function method($one, $two): void
	{
		$this->requireInt($one); // error: instance of C
		$this->requireInt($two); // error: instance of B
	}

	private function requireInt(int $n): void {}
}
