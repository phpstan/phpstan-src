<?php // lint >= 8.1

namespace EnumImplements;

interface FooInterface
{

}

class FooClass
{

}

trait FooTrait
{

}

enum FooEnum
{

}

enum Foo implements FooInterface
{

}

enum Foo2 implements FOOInterface
{

}

enum Foo3 implements FooClass
{

}

enum Foo4 implements FooTrait
{

}

enum Foo5 implements FooEnum
{

}

enum Foo6 implements NonexistentInterface
{

}

enum Foo7 implements FOOEnum
{

}
