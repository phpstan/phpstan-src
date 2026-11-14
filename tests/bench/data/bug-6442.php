<?php

/*
 * instructions: 26.61M
 * cycles: 41.56M
 * memory: 211.6KiB
 */

namespace Bug6442;

trait T
{
	public function x(): void
	{
		\PHPStan\dumpType(parent::class);
	}
}

class A {}

class B extends A
{
	use T;
}

$a = new class() extends B
{
	use T;
};

