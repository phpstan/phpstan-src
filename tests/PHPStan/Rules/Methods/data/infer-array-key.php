<?php

namespace InferArrayKey;

use function PHPStan\Analyser\assertType;

/**
 * @implements \IteratorAggregate<int, \stdClass>
 */
class Foo implements \IteratorAggregate
{

	/** @var \stdClass[] */
	private $items;

	public function getIterator()
	{
		$it = new \ArrayIterator($this->items);
		assertType('mixed', $it->key());

		return $it;
	}

}
