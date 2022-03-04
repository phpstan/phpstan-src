<?php

namespace GenericsInferCollection;

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

class Foo
{

	public function doFoo()
	{
		$this->doBar(new ArrayCollection());
		$this->doBar(new ArrayCollection([]));
		$this->doBar(new ArrayCollection(['foo', 'bar']));
	}

	/**
	 * @param ArrayCollection<int, int> $c
	 * @return void
	 */
	public function doBar(ArrayCollection $c)
	{

	}

}

class Bar
{

	public function doFoo()
	{
		$this->doBar(new ArrayCollection2());
		$this->doBar(new ArrayCollection2([]));
		$this->doBar(new ArrayCollection2(['foo', 'bar']));
	}

	/**
	 * @param ArrayCollection2<int, int> $c
	 * @return void
	 */
	public function doBar(ArrayCollection2 $c)
	{

	}

}
