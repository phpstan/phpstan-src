<?php

namespace Bug4743;

use function PHPStan\Testing\assertType;

class Node {}

/**
 * @template T of Node
 */
class NodeList {

	/**
	 * @phpstan-var array<T>
	 */
	private $nodes;

	/**
	 * @phpstan-param array<T> $nodes
	 */
	public function __construct(array $nodes)
	{
		$this->nodes = $nodes;
	}

	public function splice(int $offset, int $length): void
	{
		$newNodes = array_splice($this->nodes, $offset, $length);

		assertType('array<T of Bug4743\\Node (class Bug4743\\NodeList, argument)>', $this->nodes);
		assertType('array<T of Bug4743\\Node (class Bug4743\\NodeList, argument)>', $newNodes);
	}
}
