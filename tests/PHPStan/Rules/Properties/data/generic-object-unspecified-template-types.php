<?php

namespace GenericObjectUnspecifiedTemplateTypes;

class Foo
{

	/** @var MyObject<int, string> */
	private $obj;

	public function __construct()
	{
		$this->obj = new MyObject();
	}

}

/**
 * @template TKey of array-key
 * @template T
 */
class ArrayCollection
{

	/**
	 * @param array<TKey, T> $items
	 */
	public function __construct(array $items = [])
	{

	}

}

/**
 * @template TKey of array-key
 * @template T
 */
class ArrayCollection2
{

	public function __construct(array $items = [])
	{

	}

}

class Bar
{

	/** @var ArrayCollection<int, int> */
	private $ints;

	public function __construct()
	{
		$this->ints = new ArrayCollection();
	}

	public function doFoo()
	{
		$this->ints = new ArrayCollection([]);
	}

	public function doBar()
	{
		$this->ints = new ArrayCollection(['foo', 'bar']);
	}

}

class Baz
{

	/** @var ArrayCollection2<int, int> */
	private $ints;

	public function __construct()
	{
		$this->ints = new ArrayCollection2();
	}

	public function doFoo()
	{
		$this->ints = new ArrayCollection2([]);
	}

	public function doBar()
	{
		$this->ints = new ArrayCollection2(['foo', 'bar']);
	}

}

/**
 * @template TKey of array-key
 * @template TValue
 */
class MyObject
{
	/**
	 * @param array<TKey, TValue>|object $input
	 */
	public function __construct($input = null) { }
}
