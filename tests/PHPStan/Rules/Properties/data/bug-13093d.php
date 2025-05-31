<?php

declare(strict_types=1);

namespace Bug13093d;

use function array_shift;

final class ParallelProcessRunner
{
	/**
	 * @var array<int, string>
	 */
	private array $nextMutantProcessKillerContainer = [];

	static private string $prop;

	public function fillBucketOnce(array &$killer): int
	{
		if ($this->nextMutantProcessKillerContainer !== []) {
			self::$prop = array_shift($this->nextMutantProcessKillerContainer);
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

	static private string $prop;

	public function fillBucketOnce(array &$killer): int
	{
		$name = 'prop';
		if ($this->nextMutantProcessKillerContainer !== []) {
			self::${$name} = array_shift($this->nextMutantProcessKillerContainer);
		}

		return 0;
	}

}


