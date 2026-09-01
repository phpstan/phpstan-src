<?php declare(strict_types = 1);

namespace Bug13472;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class Foo
{
	public function dummyConsume(int $v): void {}

	public function testOverwrittenAndUnused(): int
	{
		$v = 1; // this assign should be reported
		$v = 2;

		return $v;
	}

	public function testOverwrittenButUsed(): int
	{
		$v = 1;
		$this->dummyConsume($v);
		$v = 10;

		return $v;
	}

	public function testOverwrittenButUsed2(): int
	{
		$v = 1;
		$v = $v + 1;

		return $v;
	}

	/** @param list<string> $possiblyEmptyList */
	public function testOverwrittenButUsed3(array $possiblyEmptyList): int
	{
		$v = 1;
		foreach ($possiblyEmptyList as $item) {
			$v = 2;
		}

		return $v;
	}
}
