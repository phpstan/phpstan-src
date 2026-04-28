<?php declare(strict_types = 1);

namespace Bug14549Rule;

class Foo
{
	public function foo(array $task): void
	{
		if (\is_callable($task)) {
			$this->call($task);
		}
	}

	/**
	 * @param array<int> $task
	 */
	public function call(array $task): void
	{
	}
}
