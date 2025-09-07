<?php // lint >= 8.0

namespace Bug13438b;

use LogicException;

class Test
{
	/**
	 * @param non-empty-list<int> $queue
	 */
	public function __construct(
		private array $queue,
	)
	{
	}

	public function test1(): int
	{
		return array_pop($this->queue)
			?? throw new LogicException('queue is empty');
	}

	public function test2(): int
	{
		return array_pop($this->queue);
	}
}
