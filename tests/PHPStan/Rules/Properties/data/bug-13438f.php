<?php // lint >= 8.0

namespace Bug13438f;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class Test
{
	/**
	 * @param array<int, non-empty-list<int>> $queue
	 */
	public function __construct(
		private array $queue,
	) {
	}

	public function test1(): void
	{
		array_shift($this->queue[5]); // no longer is non-empty-list<int> after this
	}

	public function test2(): void
	{
		$this->queue[5] = []; // normally it works thanks to processAssignVar
	}

}
