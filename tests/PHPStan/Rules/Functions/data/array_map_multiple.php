<?php

namespace ArrayMapMultipleCallTest;

/**
 * @template TKey as array-key
 * @template TValue
 */
class Collection
{
	/**
	 * @var array<TKey, TValue>
	 */
	protected $items = [];

	/**
	 * @param  array<TKey, TValue>  $items
	 * @return void
	 */
	public function __construct($items)
	{
		$this->items = $items;
	}

	/**
	 * @template TMapValue
	 *
	 * @param  callable(TValue, TKey): TMapValue  $callback
	 * @return self<TKey, TMapValue>
	 */
	public function map(callable $callback)
	{
		$keys = array_keys($this->items);

		$items = array_map($callback, $this->items, $keys);

		return new self(array_combine($keys, $items));
	}

	/**
	 * @return array<TKey, TValue>
	 */
	public function all()
	{
		return $this->items;
	}
}

class Foo
{

	public function doFoo(): void
	{
		array_map(function (int $a, string $b) {

		}, [1, 2], ['foo', 'bar']);

		array_map(function (int $a, int $b) {

		}, [1, 2], ['foo', 'bar']);
	}

	public function arrayMapNull(): void
	{
		array_map(null, [1, 2], [3, 4]);
	}

}
