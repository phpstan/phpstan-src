<?php

namespace VarTagChangedExprType;

class Foo
{

	public function doFoo(int $foo)
	{
		/** @var int */
		return $foo;
	}

	public function doBar(int $foo)
	{
		/** @var string */
		return $foo;
	}

}

class Bar
{

	/** @var int */
	private $foo;

	public function doFoo()
	{
		/** @var int */
		return $this->foo;
	}

	public function doBar()
	{
		/** @var string */
		return $this->foo;
	}

}

class Baz
{

	public function doFoo(int $foo)
	{
		/** @var int $foo */
		return $this->doBar($foo);
	}

	public function doBar(int $foo)
	{
		/** @var string $foo */
		return $this->doFoo($foo);
	}

}

class Lorem
{

	public function doFoo(int $foo)
	{
		/** @var int $foo */
		if ($foo) {

		}
	}

	public function doBar(int $foo)
	{
		/** @var string $foo */
		if ($foo) {

		}
	}

}
