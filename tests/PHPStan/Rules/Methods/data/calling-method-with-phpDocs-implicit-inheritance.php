<?php

namespace MethodWithPhpDocsImplicitInheritance;

interface FooInterface
{

	/**
	 * @param string $str
	 */
	public function doBar($str);

}

class Foo implements FooInterface
{

	/**
	 * @param int $i
	 */
	public function doFoo($i)
	{

	}

	public function doBar($str)
	{

	}

}

class Bar extends Foo
{

	public function doFoo($i)
	{

	}

}

class Baz extends Bar
{

	public function doFoo($i)
	{

	}

}

function () {
	$baz = new Baz();
	$baz->doFoo(1);
	$baz->doFoo('1');
	$baz->doBar('1');
	$baz->doBar(1);
};

class Lorem
{

	/**
	 * @param B $b
	 * @param C $c
	 * @param A $a
	 * @param D $d
	 */
	public function doLorem($a, $b, $c, $d)
	{

	}

}

class Ipsum extends Lorem
{

	public function doLorem($x, $y, $z, $d)
	{

	}

}

function (Ipsum $ipsum, A $a, B $b, C $c, D $d): void {
	$ipsum->doLorem($a, $b, $c, $d);
	$ipsum->doLorem(1, 1, 1, 1);
};

class Dolor extends Ipsum
{

	public function doLorem($g, $h, $i, $d)
	{

	}

}

function (Dolor $ipsum, A $a, B $b, C $c, D $d): void {
	$ipsum->doLorem($a, $b, $c, $d);
	$ipsum->doLorem(1, 1, 1, 1);
};
