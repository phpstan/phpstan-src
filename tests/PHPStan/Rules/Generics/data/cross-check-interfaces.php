<?php

namespace CrossCheckInterfaces;

final class Item
{
}

/**
 * @extends \Traversable<int, Item>
 */
interface ItemListInterface extends \Traversable
{
}

/**
 * @implements \IteratorAggregate<int, string>
 */
final class ItemList implements \IteratorAggregate, ItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}

/**
 * @implements \IteratorAggregate<int, Item>
 */
final class ItemList2 implements \IteratorAggregate, ItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}
