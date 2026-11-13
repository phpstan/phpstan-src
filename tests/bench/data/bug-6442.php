<?php

/*
 * instructions: 36.17M
 * cycles: 53.17M
 * memory: 36.85MiB
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

