<?php

namespace Bug3034;

/**
 * @implements \IteratorAggregate<int, array{name: string, value: string}>
 */
class HelloWorld implements \IteratorAggregate
{
	/**
	 * @var array<int, array{name: string, value: string}>
	 */
	private $list;

	/**
	 * @return \ArrayIterator<int, array{name: string, value: string}>
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->list);
	}
}
