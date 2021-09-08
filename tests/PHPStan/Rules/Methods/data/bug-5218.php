<?php

namespace Bug5218;

/**
 * @phpstan-implements \IteratorAggregate<string, int>
 */
final class IA implements \IteratorAggregate
{
	/** @var array<string, mixed> */
	private $data = [];

	public function getIterator() : \Traversable {
		return new \ArrayIterator($this->data);
	}
}
