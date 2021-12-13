<?php

namespace AppendedArrayItem;

class Foo
{

	/** @var int[] */
	private $integers;

	/** @var callable[] */
	private $callables;

	public function doFoo()
	{
		$this->integers[] = 4;
		$this->integers['foo'] = 5;
		$this->integers[] = 'foo';
		$this->callables[] = [$this, 'doFoo'];
		$this->callables[] = [1, 2, 3];
		$this->callables[] = ['Closure', 'bind'];
		$this->callables[] = 'strpos';
		$this->callables[] = [__CLASS__, 'classMethod'];
		$world = 'world';
		$this->callables[] = ['Foo', "Hello $world"];

		$this->integers[] = &$world;
	}

	public function assignOp()
	{
		$this->integers[0] .= 'foo';
	}

}

class Bar
{

	/** @var (callable(): string)[] */
	private $stringCallables;

	public function doFoo()
	{
		$this->stringCallables[] = function (): int {
			return 1;
		};
	}

	public function doBar()
	{
		$this->stringCallables[] = function (): string {
			return 1;
		};
	}

}

class Baz
{

	/** @var array<static> */
	public $staticProperty;

}

class Lorem extends Baz
{

}

function (Lorem $lorem)
{
	$lorem->staticProperty[] = new Lorem();
};

function (Lorem $lorem)
{
	$lorem->staticProperty[] = new Baz();
};

class ArrayAccess
{
	/** @var \ArrayAccess<int, string> */
	private $collection1;

	/** @var \ArrayAccess<int, string>&\Countable */
	private $collection2;

	/** @var \ArrayAccess<int, string>&\Countable&iterable<int, string> */
	private $collection3;

	/** @var \SplObjectStorage<int, string> */
	private $collection4;

	public function doFoo()
	{
		$this->collection1[] = 'foo';
		$this->collection1[] = 1;

		$this->collection2[] = 'foo';
		$this->collection2[] = 2;

		$this->collection3[] = 'foo';
		$this->collection3[] = 3;

		$this->collection4[] = 'foo';
		$this->collection4[] = 4;
	}
}
