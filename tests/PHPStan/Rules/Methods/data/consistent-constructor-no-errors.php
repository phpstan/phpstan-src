<?php

namespace ConsistentConstructorNoErrors;

class NoParent
{
	public function __construct(int $a)
	{
	}
}

class ParentWithNoConstructor {}

class ParentWithNoConstructorChild extends ParentWithNoConstructor
{
	public function __construct(int $a)
	{
	}
}

/** @phpstan-consistent-constructor */
class ParentWithConstructor
{
	public function __construct(int $a)
	{
	}
}

class ParentWithConstructorChild extends ParentWithConstructor
{
	public function __construct(int $b)
	{
	}
}

class A {}
class B extends A {}

/** @phpstan-consistent-constructor */
class Foo
{

	public function __construct(B $b) {}
}

class Baz extends Foo
{

	public function __construct(A $b) {}
}
