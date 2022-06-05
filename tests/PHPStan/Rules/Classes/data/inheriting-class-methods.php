<?php

namespace InheritingClassMethods;

interface FooInterface
{
	public function bar(int $i): void;
}

class ParentFoo
{
	public function bar(): int
	{
	}
}

class Foo extends ParentFoo implements FooInterface {}

interface ChildInterface extends FooInterface {}

class Bar extends ParentFoo implements ChildInterface {}

class A {}

class B extends A {}

interface CovariantInterface
{
	public function bar(): A;
}

class CovariantParent
{
	public function bar(): B
	{
	}
}

class CovariantTest extends CovariantParent implements CovariantInterface {}
