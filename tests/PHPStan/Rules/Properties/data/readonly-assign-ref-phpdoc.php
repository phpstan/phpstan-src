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

/** @phpstan-immutable */
class Immutable
{

	/** @var int */
	private $foo;

	/** @var int */
	public $bar;

	public function doFoo()
	{
		$foo = &$this->foo;
		$bar = &$this->bar;
	}

}

/** @immutable */
class A
{

	/** @var string */
	public $a;

	public function mod()
	{
		$a = &$this->a;
	}

}

class B extends A
{

	/** @var string */
	public $b;

	public function mod()
	{
		$b = &$this->b;
		$a = &$this->a;
	}

}

class C extends B
{

	/** @var string */
	public $c;

	public function mod()
	{
		$c = &$this->c;
	}

}
