<?php declare(strict_types = 1);

namespace PhpDoc\ReturnTag;

class A {}
class B extends A {}
class C extends B {}

class GrandparentClass
{
	/** @return B */
	public function method() { return new B(); }
}

interface InterfaceC
{
	/** @return C */
	public function method();
}

interface InterfaceA
{
	/** @return A */
	public function method();
}

class ParentClass extends GrandparentClass implements InterfaceC, InterfaceA
{
	/** Some comment */
	public function method() { return new B(); } // error: should return C
}

class ChildClass extends ParentClass
{
	public function method() { return 1; } // error: should return C
}
