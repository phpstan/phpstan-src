<?php

namespace Bug6732;

/**
 * @template TKey of array-key
 * @template TValue
 */
class ArrayCollection
{

	/** @var array<TKey, TValue> */
	public array $items;

	/**
	 * @param array<TKey, TValue> $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

}

class Foo
{

	/** @var ArrayCollection<int, int> $ints */
	public ArrayCollection $ints;

	/** @var ArrayCollection<int, string> $strings */
	public ArrayCollection $strings;

	public function __construct() {
		$array = new ArrayCollection([]);
		$this->ints = $array;
		$this->strings = $array;
	}

}
