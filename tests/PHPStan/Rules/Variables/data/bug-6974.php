<?php declare(strict_types = 1);

namespace Bug6974;

class A
{
	public function test(): void
	{
		$a = [];
		$b = [];
		array_push($a, ...$b);
		$c = empty($a) ? 'empty' : 'non-empty';
	}
}
