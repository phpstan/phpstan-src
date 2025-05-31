<?php

declare(strict_types=1);

namespace Bug13093c;

use function array_shift;

final class ParallelProcessRunner
{
	/**
	 * @var array<int, string>
	 */
	private array $nextMutantProcessKillerContainer = [];

	private string $prop;

	public function fillBucketOnce(array &$killer): int
	{
		if ($this->nextMutantProcessKillerContainer !== []) {
			$this->prop = array_shift($this->nextMutantProcessKillerContainer);
		}

		return 0;
	}

}

final class ParallelProcessRunner2
{
	/**
	 * @var array<int, string>
	 */
	private array $nextMutantProcessKillerContainer = [];

	private string $prop;

	public function fillBucketOnce(array &$killer): int
	{
		$name = 'prop';
		if ($this->nextMutantProcessKillerContainer !== []) {
			$this->{$name} = array_shift($this->nextMutantProcessKillerContainer);
		}

		return 0;
	}

}

