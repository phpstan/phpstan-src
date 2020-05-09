<?php

namespace ReturnTypePhpDocMergeReturnInherited;

class A {}
class B extends A {}
class C extends B {}
class D extends A {}

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
	public function method()
	{
		return new A();
	}
}

class ChildClass extends ParentClass
{
	public function method()
	{
		return new A();
	}
}

class ChildClass2 extends ParentClass
{
	/**
	 * @return D
	 */
	public function method()
	{
		return new B();
	}
}
