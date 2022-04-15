<?php declare(strict_types = 1);

namespace Bug6974;

class A
{
	public function test1(): void
	{
		$a = [];
		$b = [];
		array_push($a, ...$b);
		$c = empty($a) ? 'empty' : 'non-empty';
	}

	public function test2(): void
	{
		$a = [];
		/** @var mixed[] $b */
		$b = [];
		array_push($a, ...$b);
		$c = empty($a) ? 'empty' : 'non-empty';
	}

	public function test3(): void
	{
		$a = [];
		/** @var non-empty-array<mixed> $b */
		$b = [];
		array_push($a, ...$b);
		$c = empty($a) ? 'empty' : 'non-empty';
	}
}
