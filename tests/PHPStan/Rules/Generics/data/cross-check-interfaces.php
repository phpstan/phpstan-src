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

/**
 * @extends \Traversable<int, array{a: int, ...<int, int>}>
 */
interface ShapedItemListInterface extends \Traversable
{
}

/**
 * `IteratorAggregate<int, array{a: int, ...<int, int>}>` and the inherited
 * `Traversable<int, array{a: int, ...<int, int>}>` resolve to the same
 * unsealed array shape — `equals()` deduplicates them and no
 * `interfaceConflict` is reported.
 *
 * @implements \IteratorAggregate<int, array{a: int, ...<int, int>}>
 */
final class ShapedItemList implements \IteratorAggregate, ShapedItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}

/**
 * Different unsealed value type on the two sides — `equals()` returns
 * false on the unsealed extras, so the conflict surfaces.
 *
 * @implements \IteratorAggregate<int, array{a: int, ...<int, string>}>
 */
final class ShapedItemListMismatch implements \IteratorAggregate, ShapedItemListInterface
{
	public function getIterator(): \Traversable
	{
		return new \ArrayIterator([]);
	}
}
