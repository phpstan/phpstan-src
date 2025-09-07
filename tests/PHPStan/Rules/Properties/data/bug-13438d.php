<?php

namespace Bug13438d;

class Test
{
	/**
	 * @param array{} $queue
	 */
	public function __construct(
		private array $queue,
	)
	{
	}

	public function test1(): int
	{
		return array_push($this->queue, 1);
	}
}
