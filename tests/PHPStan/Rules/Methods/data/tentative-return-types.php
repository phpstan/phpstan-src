<?php

namespace TentativeReturnTypes;

class Foo implements \IteratorAggregate
{

	public function getIterator()
	{

	}

}

class Bar implements \IteratorAggregate
{

	#[\ReturnTypeWillChange]
	public function getIterator()
	{

	}

}

class Baz implements \IteratorAggregate
{

	#[\ReturnTypeWillChange]
	public function getIterator(): string
	{

	}

}

class Lorem implements \IteratorAggregate
{

	public function getIterator(): string
	{

	}

}

class TypedIterator implements \Iterator
{

	public function current(): mixed
	{
	}

	public function next(): void
	{
	}

	public function key(): mixed
	{
	}

	public function valid(): bool
	{
	}

	public function rewind(): void
	{
	}

}

class UntypedIterator implements \Iterator
{

	public function current()
	{
	}

	public function next()
	{
	}

	public function key()
	{
	}

	public function valid()
	{
	}

	public function rewind()
	{
	}

}


abstract class MetadataFilter extends \FilterIterator
{
	/**
	 * @return \ArrayIterator<int, string>
	 */
	#[\ReturnTypeWillChange]
	public function getInnerIterator()
	{

	}
}
