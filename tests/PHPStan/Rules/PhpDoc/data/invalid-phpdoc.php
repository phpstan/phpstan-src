<?php

namespace InvalidPhpDoc;

/**
 * @param
 * @param $invalid
 * @param $invalid Foo
 * @param A & B | C $paramNameA
 * @param (A & B $paramNameB
 * @param ~A & B $paramNameC
 *
 * @var
 * @var $invalid
 * @var $invalid Foo
 *
 * @return
 * @return [int, string]
 * @return A & B | C
 *
 * @param Foo $valid
 * @return Foo
 * @uses Foo::bar()
 */
function foo()
{

	/** @var \\Foo|\Bar $test */
	$test = doFoo();

}

/**
 * This is a class description that talks about some phpDoc @param and continues
 * to talk about it even more
 */
class Foo
{

}

class Bar
{

	/**
	 * @psalm-param list() $a
	 */
	public function doFoo($a)
	{

	}

}

class Baz
{

	/** @var callable(int) */
	private $fooProperty;

	/** @var (Foo|Bar */
	private $barProperty;

}

class InlineThrows
{

	public function doFoo()
	{
		/** @throws (\Exception */
		$i = 1;
	}

}

class ClassConstant
{

	/** @var (Foo|Bar */
	const FOO = 1;

}

class AboveProperty
{

	/** @var (Foo& */
	private $foo;

	/** @var (Foo& */
	private const TEST = 1;

}

class AboveReturn
{

	public function doFoo(): string
	{
		/** @var (Foo& */
		return doFoo();
	}

}
