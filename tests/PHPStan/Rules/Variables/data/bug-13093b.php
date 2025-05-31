<?php

declare(strict_types=1);

namespace Bug13093b;

use function array_shift;

final class ParallelProcessRunner
{
	/**
	 * @var array<int, string>
	 */
	private array $nextMutantProcessKillerContainer = [];

	public function fillBucketOnce(string &$killer): int
	{
		if ($this->nextMutantProcessKillerContainer !== []) {
			$killer = array_shift($this->nextMutantProcessKillerContainer);
		}

		return 0;
	}

}

