<?php

namespace ReadOnlyPropertyAssignRefPhpDoc;

class Foo
{

	/**
	 * @var int
	 * @readonly
	 */
	private $foo;

	/**
	 * @var int
	 * @readonly
	 */
	public $bar;

	public function doFoo()
	{
		$foo = &$this->foo;
		$bar = &$this->bar;
	}

}

class Bar
{

	public function doBar(Foo $foo)
	{
		$a = &$foo->foo; // private
		$b = &$foo->bar;
	}

}
