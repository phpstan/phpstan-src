<?php // lint >= 8.1

namespace MethodIntersectionTypes;

interface Foo
{

}

interface Bar
{

}

class Lorem
{

}

class Ipsum
{

}

class Clazz
{

	public function doFoo(Foo&Bar $a): Foo&Bar
	{

	}

	public function doBar(Lorem&Ipsum $a): Lorem&Ipsum
	{

	}

	public function doBaz(int&mixed $a): int&mixed
	{

	}

}
