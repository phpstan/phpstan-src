<?php

namespace InferArrayKey;

use function PHPStan\Testing\assertType;

/**
 * @implements \IteratorAggregate<int, \stdClass>
 */
class Foo implements \IteratorAggregate
{

	/** @var \stdClass[] */
	private $items;

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('(int|string)', $it->key());

		return $it;
	}

}

/**
 * @implements \IteratorAggregate<int, \stdClass>
 */
class Bar implements \IteratorAggregate
{

	/** @var array<int, \stdClass> */
	private $items;

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('int', $it->key());

		return $it;
	}

}

/**
 * @implements \IteratorAggregate<string, \stdClass>
 */
class Baz implements \IteratorAggregate
{

	/** @var array<string, \stdClass> */
	private $items;

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('string', $it->key());

		return $it;
	}

}

/**
 * @implements \IteratorAggregate<int, \stdClass>
 */
class Lorem implements \IteratorAggregate
{

	/** @var array<\stdClass> */
	private $items;

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('(int|string)', $it->key());

		return $it;
	}

}

/**
 * @implements \IteratorAggregate<int|string, \stdClass>
 */
class Ipsum implements \IteratorAggregate
{

	/** @var array<int|string, \stdClass> */
	private $items;

	#[\ReturnTypeWillChange]
	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('int|string', $it->key());

		return $it;
	}

}
