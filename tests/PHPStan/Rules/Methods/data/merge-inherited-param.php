<?php declare(strict_types = 1);

namespace CallMethodsPhpDocMergeParamInherited;

class A {}
class B extends A {}
class C extends B {}
class D {}

class GrandparentClass
{
	/** @param A $one */
	public function method($one, $two): void {}
}

class ParentClass extends GrandparentClass
{
	/** @param B $dos */
	public function method($uno, $dos): void
	{

	}
}

class ChildClass extends ParentClass
{
	/** @param C $one */
	public function method($one, $two): void
	{

	}

}

function (ParentClass $foo) {
	$foo->method(new A(), new B()); // ok
	$foo->method(new D(), new D()); // expects A, B
};

function (ChildClass $foo) {
	$foo->method(new C(), new B()); // ok
	$foo->method(new B(), new D()); // expects C, B
};
