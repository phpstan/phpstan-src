<?php

declare(strict_types=1);

namespace Bug13093;

use function array_shift;
use function count;
use Generator;
use function PHPStan\debugScope;
use function PHPStan\dumpType;

class MutantProcessContainer {}

final class ParallelProcessRunner
{
	/**
	 * @var array<int, MutantProcessContainer>
	 */
	private array $nextMutantProcessKillerContainer = [];

	/**
	 * @param MutantProcessContainer[] $bucket
	 * @param Generator<MutantProcessContainer> $input
	 */
	public function fillBucketOnce(array &$bucket, Generator $input, int $threadCount): int
	{
		if (count($bucket) >= $threadCount || !$input->valid()) {
			if ($this->nextMutantProcessKillerContainer !== []) {
				$bucket[] = array_shift($this->nextMutantProcessKillerContainer);
			}

			return 0;
		}

		return 1;
	}

}

