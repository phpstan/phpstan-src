<?php // lint >= 8.1

namespace OverridingFinalConstant;

class Foo
{

	const NON_FINAL = 1;
	final const FOO = 1;
	final const BAR = 1;

}

class Bar extends Foo
{

	const NON_FINAL = 2;
	const FOO = 2;
	final const BAR = 2;

}

interface FooInterface
{

	const FOO = 1;
	final const BAR = 2;

}

class Baz implements FooInterface
{

	const FOO = 3;
	const BAR = 4;

}

interface BarInterface
{

	/** @var int */
	const FOO = 1;

}

class Lorem implements BarInterface
{

	/** @var string */
	const FOO = 1;

}
