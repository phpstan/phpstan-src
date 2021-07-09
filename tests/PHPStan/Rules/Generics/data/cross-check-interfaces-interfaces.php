<?php

namespace CrossCheckInterfacesInInterfaces;

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
 * @extends \IteratorAggregate<int, string>
 */
interface ItemList extends \IteratorAggregate, ItemListInterface
{

}

/**
 * @extends \IteratorAggregate<int, Item>
 */
interface ItemList2 extends  \IteratorAggregate, ItemListInterface
{

}

interface ItemList3 extends ItemList // do not report
{

}

/**
 * @extends \Traversable<int, mixed>
 */
interface ResultStatement extends \Traversable
{

}

interface Statement extends ResultStatement
{

}
