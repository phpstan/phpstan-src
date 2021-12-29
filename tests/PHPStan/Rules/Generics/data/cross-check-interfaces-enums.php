<?php

namespace CrossCheckInterfacesEnums;

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
enum ItemList implements \IteratorAggregate, ItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}

/**
 * @implements \IteratorAggregate<int, Item>
 */
enum ItemList2 implements \IteratorAggregate, ItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}
